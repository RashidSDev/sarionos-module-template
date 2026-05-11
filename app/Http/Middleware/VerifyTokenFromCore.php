<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerifyTokenFromCore
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('logout') || $request->is('auth/callback')) {
            return $next($request);
        }

        $payload = null;

        if ($request->hasCookie('sarionos_sso')) {
            try {
                $payload = json_decode(Crypt::decrypt($request->cookie('sarionos_sso')), true);
            } catch (\Throwable $e) {
                Log::warning('[MODULE][VerifyTokenFromCore] invalid sarionos_sso cookie', [
                    'error' => $e->getMessage(),
                ]);

                return redirect('/logout');
            }

            if (! is_array($payload)) {
                return redirect('/logout');
            }
        }

        $workspaceContext = $this->workspaceContextPayload($request);

        if (
            session('sarionos_logged_in') === true &&
            session()->has('sarionos_token') &&
            session()->has('sarionos_user_uuid')
        ) {
            if (is_array($payload)) {
                if (! $this->payloadIsValid($payload)) {
                    return redirect('/logout');
                }

                $this->syncSessionFromCookiePayload($payload);
            }

            $this->syncWorkspaceContextFromCookie($workspaceContext);

            $alive = $this->coreTokenIsAlive(session('sarionos_token'));

            if ($alive === false) {
                return redirect('/logout');
            }

            return $next($request);
        }

        if (is_array($payload)) {
            if (! $this->payloadIsValid($payload)) {
                return redirect('/logout');
            }

            $alive = $this->coreTokenIsAlive($payload['token']);

            if ($alive === false) {
                return redirect('/logout');
            }

            $this->startSessionFromCookiePayload($payload);
            $this->syncWorkspaceContextFromCookie($workspaceContext);

            return $next($request);
        }

        $core = rtrim(env('SARIONOS_CORE_URL'), '/');
        $self = rtrim(env('SARIONOS_SELF_URL'), '/');

        return redirect()->away($core . '/login?redirect=' . urlencode($self . '/auth/callback'));
    }

    private function payloadIsValid(array $payload): bool
    {
        $userName = $payload['user_name'] ?? ($payload['name'] ?? null);

        return ! empty($payload['token'])
            && ! empty($payload['user_uuid'])
            && ! empty($payload['role_id'])
            && ! empty($userName);
    }

    private function startSessionFromCookiePayload(array $payload): void
    {
        $userName = $payload['user_name'] ?? ($payload['name'] ?? null);

        session([
            'sarionos_logged_in'             => true,
            'sarionos_token'                 => $payload['token'],
            'sarionos_user_name'             => $userName,
            'sarionos_user_uuid'             => $payload['user_uuid'],
            'sarionos_role_id'               => $payload['role_id'],
            'force_refresh_context'          => true,
            'force_refresh_users'            => true,
            'force_refresh_modules'          => true,
        ]);
    }

    private function syncSessionFromCookiePayload(array $payload): void
    {
        $cookieToken = (string) ($payload['token'] ?? '');
        $sessionToken = (string) session('sarionos_token', '');

        $tokenChanged = $cookieToken !== '' && $sessionToken !== $cookieToken;

        if ($tokenChanged) {
            session()->forget([
                'sarionos_core_alive_checked_at',
                'sarionos_core_alive_last_ok',
                'sarionos_token_expires_at',
            ]);

            Log::info('[MODULE][VerifyTokenFromCore] token changed from shared SSO cookie');
        }

        session([
            'sarionos_token'     => $cookieToken,
            'sarionos_user_name' => $payload['user_name'] ?? ($payload['name'] ?? session('sarionos_user_name')),
            'sarionos_user_uuid' => $payload['user_uuid'],
            'sarionos_role_id'   => $payload['role_id'],
        ]);
    }

    private function workspaceContextPayload(Request $request): ?array
    {
        if (! $request->hasCookie('sarionos_workspace_context')) {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decrypt($request->cookie('sarionos_workspace_context')), true);
        } catch (\Throwable $e) {
            Log::warning('[MODULE][VerifyTokenFromCore] invalid sarionos_workspace_context cookie', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return is_array($payload) ? $payload : null;
    }

    private function syncWorkspaceContextFromCookie(?array $payload): void
    {
        if (! is_array($payload)) {
            return;
        }

        $cookieWorkspaceUuid = (string) ($payload['workspace_uuid'] ?? '');
        $cookieWorkspaceName = $payload['workspace_name'] ?? null;

        if ($cookieWorkspaceUuid === '') {
            return;
        }

        $sessionWorkspaceUuid = (string) session('sarionos_active_workspace_uuid', '');

        if ($sessionWorkspaceUuid !== '' && $sessionWorkspaceUuid === $cookieWorkspaceUuid) {
            return;
        }

        session()->forget([
            'sarionos_workspace_users',
            'sarionos_workspace_modules',
            'sarionos_user_workspaces',
            'sarionos_context_workspace_uuid',
            'sarionos_core_alive_checked_at',
            'sarionos_core_alive_last_ok',
            'sarionos_token_expires_at',
        ]);

        session([
            'sarionos_active_workspace_uuid' => $cookieWorkspaceUuid,
            'sarionos_active_workspace_name' => $cookieWorkspaceName,
            'force_refresh_context'          => true,
            'force_refresh_users'            => true,
            'force_refresh_modules'          => true,
        ]);

        Log::info('[MODULE][VerifyTokenFromCore] workspace changed from workspace context cookie', [
            'old_workspace_uuid' => $sessionWorkspaceUuid,
            'new_workspace_uuid' => $cookieWorkspaceUuid,
        ]);
    }

    private function coreTokenIsAlive(string $token): bool|null
    {
        $cacheForSeconds = 60;

        $lastCheck = session('sarionos_core_alive_checked_at');
        $lastOk    = session('sarionos_core_alive_last_ok');

        if ($lastCheck && (time() - (int) $lastCheck) < $cacheForSeconds) {
            return $lastOk;
        }

        $core = rtrim(env('SARIONOS_CORE_URL'), '/');

        try {
            $res = Http::withToken($token)
                ->acceptJson()
                ->timeout(5)
                ->get($core . '/api/sso/validate');
        } catch (\Throwable $e) {
            Log::warning('[MODULE][VerifyTokenFromCore] validate exception', [
                'error' => $e->getMessage(),
                'session_id' => session()->getId(),
                'workspace_uuid' => session('sarionos_active_workspace_uuid'),
            ]);

            session([
                'sarionos_core_alive_checked_at' => time(),
                'sarionos_core_alive_last_ok' => null,
            ]);

            return null;
        }

        if (in_array($res->status(), [401, 403], true)) {
            session([
                'sarionos_core_alive_checked_at' => time(),
                'sarionos_core_alive_last_ok' => false,
            ]);

            return false;
        }

        if (! $res->ok()) {
            Log::warning('[MODULE][VerifyTokenFromCore] validate non-ok response', [
                'status' => $res->status(),
                'body' => $res->json(),
                'session_id' => session()->getId(),
                'workspace_uuid' => session('sarionos_active_workspace_uuid'),
            ]);

            session([
                'sarionos_core_alive_checked_at' => time(),
                'sarionos_core_alive_last_ok' => null,
            ]);

            return null;
        }

        $ok = ($res->json('valid') === true);

        if (! $ok) {
            session([
                'sarionos_core_alive_checked_at' => time(),
                'sarionos_core_alive_last_ok' => false,
            ]);

            return false;
        }

        $expiresAt = $res->json('expires_at');

        if ($expiresAt) {
            session(['sarionos_token_expires_at' => $expiresAt]);

            if (Carbon::parse($expiresAt)->isPast()) {
                session([
                    'sarionos_core_alive_checked_at' => time(),
                    'sarionos_core_alive_last_ok' => false,
                ]);

                return false;
            }
        }

        session([
            'sarionos_core_alive_checked_at' => time(),
            'sarionos_core_alive_last_ok' => true,
        ]);

        return true;
    }
}