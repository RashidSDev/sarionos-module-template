<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncWorkspaceFromCore
{
    public function handle(
        Request $request,
        Closure $next
    ) {
        if (
            $request->is('logout')
            || $request->is('auth/callback')
        ) {
            return $next($request);
        }

        /*
         * Core is the only scope authority.
         *
         * If Core previously resolved this module as
         * Personal/System, local workspace context is
         * irrelevant and is removed.
         */
        $effectiveScope =
            session(
                'sarionos_module_scope'
            );

        if (
            in_array(
                $effectiveScope,
                [
                    'personal',
                    'system',
                ],
                true
            )
        ) {
            session()->forget([
                'sarionos_active_workspace_uuid',
                'sarionos_active_workspace_name',
                'sarionos_is_workspace_owner',
                'sarionos_workspace_users',
                'sarionos_user_workspaces',
                'sarionos_context_workspace_uuid',
            ]);

            return $next($request);
        }

        /*
         * Before Core has resolved the scope there is
         * deliberately no workspace requirement here.
         */
        $activeWorkspaceUuid =
            (string) session(
                'sarionos_active_workspace_uuid',
                ''
            );

        $contextWorkspaceUuid =
            (string) session(
                'sarionos_context_workspace_uuid',
                ''
            );

        $workspaceChanged =
            $contextWorkspaceUuid
                !== $activeWorkspaceUuid;

        $manualRefresh =
            $request->boolean(
                'refresh_context'
            );

        if (
            $workspaceChanged
            || $manualRefresh
        ) {
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
            ]);

            session([
                'force_refresh_users' =>
                    true,

                'force_refresh_modules' =>
                    true,

                'force_refresh_context' =>
                    true,

                'sarionos_context_workspace_uuid' =>
                    $activeWorkspaceUuid,
            ]);

            Log::info(
                '[MODULE][SyncWorkspaceFromCore] CONTEXT MARKED FOR REFRESH',
                [
                    'active_workspace_uuid' =>
                        $activeWorkspaceUuid
                        !== ''
                            ? $activeWorkspaceUuid
                            : null,

                    'context_workspace_uuid' =>
                        $contextWorkspaceUuid
                        !== ''
                            ? $contextWorkspaceUuid
                            : null,

                    'refresh_context' =>
                        $manualRefresh,
                ]
            );
        }

        return $next($request);
    }
}
