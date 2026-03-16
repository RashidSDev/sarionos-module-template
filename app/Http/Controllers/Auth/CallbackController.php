<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CallbackController extends Controller
{
    public function handle(Request $request)
    {
        if (! $request->hasCookie('sarionos_sso')) {
            \Log::warning('MODULE CALLBACK → missing sarionos_sso cookie');

            return $this->redirectToCoreLogout();
        }

        try {
            $payload = json_decode(Crypt::decrypt($request->cookie('sarionos_sso')), true);
        } catch (\Throwable $e) {
            \Log::warning('MODULE CALLBACK → invalid sarionos_sso cookie', [
                'error' => $e->getMessage(),
            ]);

            return $this->redirectToCoreLogout();
        }

        if (! is_array($payload)) {
            \Log::warning('MODULE CALLBACK → decrypted payload is not an array');

            return $this->redirectToCoreLogout();
        }

        $token    = $payload['token'] ?? null;
        $userUuid = $payload['user_uuid'] ?? null;
        $roleId   = $payload['role_id'] ?? null;
        $userName = $payload['user_name'] ?? ($payload['name'] ?? null);

        $activeWorkspaceUuid = $payload['active_workspace_uuid'] ?? null;
        $activeWorkspaceName = $payload['active_workspace_name'] ?? null;
        $isOwnerBool         = (bool) ($payload['is_workspace_owner'] ?? false);

        if (! $token || ! $userUuid || ! $roleId || ! $userName) {
            \Log::warning('MODULE CALLBACK → missing required payload fields', [
                'has_token'    => (bool) $token,
                'has_userUuid' => (bool) $userUuid,
                'has_roleId'   => (bool) $roleId,
                'has_userName' => (bool) $userName,
            ]);

            return $this->redirectToCoreLogout();
        }

        session([
            'sarionos_logged_in'             => true,
            'sarionos_token'                 => $token,
            'sarionos_user_name'             => $userName,
            'sarionos_user_uuid'             => $userUuid,
            'sarionos_role_id'               => $roleId,
            'sarionos_active_workspace_uuid' => $activeWorkspaceUuid,
            'sarionos_active_workspace_name' => $activeWorkspaceName,
            'sarionos_is_workspace_owner'    => $isOwnerBool,
        ]);

        session()->put('force_refresh_users', true);
        session()->put('force_refresh_modules', true);
        session()->put('force_refresh_context', true);

        return redirect('/dashboard');
    }

    private function redirectToCoreLogout()
    {
        session()->flush();

        $core = rtrim(env('SARIONOS_CORE_URL'), '/');
        $self = rtrim(env('SARIONOS_SELF_URL'), '/');

        return redirect()->away($core . '/logout?redirect=' . urlencode($self . '/auth/callback'))
            ->withCookie(cookie()->forget('sarionos_sso', '/', '.dev.sarionos.com'));
    }
}