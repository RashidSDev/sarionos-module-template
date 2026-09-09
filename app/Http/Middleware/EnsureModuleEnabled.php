<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureModuleEnabled
{
    public function handle(
        Request $request,
        Closure $next
    ) {
        $moduleKey = trim(
            (string) config(
                'sarionos.module_key'
            )
        );

        /*
         * Core /api/me/modules is the canonical
         * entitlement catalogue for Workspace,
         * Personal and System scopes.
         */
        $modules = collect(
            session(
                'sarionos_modules',
                []
            )
        );

        $enabled =
            $modules->contains(
                fn ($module): bool =>
                    is_array($module)
                    && (
                        $module['key']
                        ?? null
                    ) === $moduleKey
            );

        if ($enabled) {
            return $next($request);
        }

        session()->forget([
            'sarionos_workspace_users',
            'sarionos_workspace_modules',
            'sarionos_modules',
            'sarionos_module_scope',
            'sarionos_user_workspaces',
            'sarionos_navigation_items',
            'sarionos_allowed_access_keys',
            'sarionos_access_route_rules',
            'sarionos_context_version',
            'force_refresh_users',
            'force_refresh_modules',
            'force_refresh_context',
            'sarionos_core_alive_checked_at',
            'sarionos_core_alive_last_ok',
        ]);

        return redirect()->away(
            rtrim(
                (string) config(
                    'sarionos.web_url'
                ),
                '/'
            )
            . '/dashboard?refresh_context=1'
        );
    }
}
