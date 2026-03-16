<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class VerifyTokenFromCore
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('logout') || $request->is('auth/callback')) {
            return $next($request);
        }

        if (
            session('sarionos_logged_in') === true &&
            session()->has('sarionos_token') &&
            session()->has('sarionos_user_uuid')
        ) {
            $alive = $this->coreTokenIsAlive(session('sarionos_token'));

            if ($alive === false) {
                return redirect('/logout');
            }

            return $next($request);
        }

        if ($request->hasCookie('sarionos_sso')) {
            try {
                $payload = json_decode(Crypt::decrypt($request->cookie('sarionos_sso')), true);
            } catch (\Throwable $e) {
                \Log::warning('[MODULE][VerifyTokenFromCore] invalid sarionos_sso cookie', [
                    'error' => $e->getMessage(),
                ]);

                return redirect('/logout');
            }

            if (! is_array($payload)) {
                return redirect('/logout');
            }

            $userName = $payload['user_name'] ?? ($payload['name'] ?? null);

            if (
                empty($payload['token']) ||
                empty($payload['user_uuid']) ||
                empty($payload['role_id']) ||
                empty($userName)
            ) {
                return redirect('/logout');
            }

            $alive = $this->coreTokenIsAlive($payload['token']);

            if ($alive === false) {
                return redirect('/logout');
            }

            session([
                'sarionos_logged_in'             => true,
                'sarionos_token'                 => $payload['token'],
                'sarionos_user_name'             => $userName,
                'sarionos_user_uuid'             => $payload['user_uuid'],
                'sarionos_role_id'               => $payload['role_id'],
                'sarionos_active_workspace_uuid' => $payload['active_workspace_uuid'] ?? null,
                'sarionos_active_workspace_name' => $payload['active_workspace_name'] ?? null,
                'sarionos_is_workspace_owner'    => (bool) ($payload['is_workspace_owner'] ?? false),
            ]);

            return $next($request);
        }

        $core = rtrim(env('SARIONOS_CORE_URL'), '/');
        $self = rtrim(env('SARIONOS_SELF_URL'), '/');

        return redirect()->away($core . '/login?redirect=' . urlencode($self . '/auth/callback'));
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
            \Log::warning('[MODULE][VerifyTokenFromCore] validate exception', [
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
            \Log::warning('[MODULE][VerifyTokenFromCore] validate non-ok response', [
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
            'sarionos_active_workspace_uuid' => $res->json('active_workspace_uuid'),
            'sarionos_active_workspace_name' => $res->json('active_workspace_name'),
            'sarionos_core_alive_checked_at' => time(),
            'sarionos_core_alive_last_ok' => true,
        ]);

        return true;
    }
}