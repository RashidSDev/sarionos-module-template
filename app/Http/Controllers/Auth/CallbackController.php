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

        if (! $token || ! $userUuid || ! $roleId || ! $userName) {
            \Log::warning('MODULE CALLBACK → missing required payload fields', [
                'has_token'    => (bool) $token,
                'has_userUuid' => (bool) $userUuid,
                'has_roleId'   => (bool) $roleId,
                'has_userName' => (bool) $userName,
            ]);

            return $this->redirectToCoreLogout();
        }

        /*
         * The callback establishes authentication only.
         *
         * It deliberately does not infer local application
         * scope from the global SSO workspace payload.
         * LoadWorkspaceContextFromCore resolves the
         * registered module through Core and then chooses
         * Personal / Workspace / System behavior.
         */
        session()->forget([
            'sarionos_active_workspace_uuid',
            'sarionos_active_workspace_name',
            'sarionos_is_workspace_owner',
            'sarionos_workspace_users',
            'sarionos_workspace_modules',
            'sarionos_user_workspaces',
            'sarionos_module_scope',
            'sarionos_navigation_items',
            'sarionos_allowed_access_keys',
            'sarionos_access_route_rules',
            'sarionos_context_version',
            'sarionos_context_workspace_uuid',
        ]);

        session([
            'sarionos_logged_in' =>
                true,

            'sarionos_token' =>
                $token,

            'sarionos_user_name' =>
                $userName,

            'sarionos_user_uuid' =>
                $userUuid,

            'sarionos_role_id' =>
                $roleId,

            'force_refresh_users' =>
                true,

            'force_refresh_modules' =>
                true,

            'force_refresh_context' =>
                true,
        ]);

        return redirect('/dashboard');
    }

    private function redirectToCoreLogout()
    {
        session()->flush();

        $core = rtrim(config('sarionos.core_url'), '/');
        $self = rtrim(config('sarionos.self_url'), '/');

        return redirect()->away($core . '/logout?redirect=' . urlencode($self . '/auth/callback'))
            ->withCookie(cookie()->forget('sarionos_sso', '/', config('sarionos.cookie_domain')));
    }
}