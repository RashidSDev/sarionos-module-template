<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncWorkspaceFromCore
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('logout') || $request->is('auth/callback')) {
            return $next($request);
        }

        $activeWorkspaceUuid = (string) session('sarionos_active_workspace_uuid', '');

        if ($activeWorkspaceUuid === '') {
            return redirect('/logout');
        }

        $contextWorkspaceUuid = (string) session('sarionos_context_workspace_uuid', '');

        $workspaceChanged = $contextWorkspaceUuid !== $activeWorkspaceUuid;
        $manualRefresh = $request->boolean('refresh_context');

        if ($workspaceChanged || $manualRefresh) {
            session()->forget([
                'sarionos_workspace_users',
                'sarionos_workspace_modules',
                'sarionos_user_workspaces',
            ]);

            session([
                'force_refresh_users' => true,
                'force_refresh_modules' => true,
                'force_refresh_context' => true,
                'sarionos_context_workspace_uuid' => $activeWorkspaceUuid,
            ]);

            Log::info('[MODULE][SyncWorkspaceFromCore] LOCAL CONTEXT MARKED FOR REFRESH', [
                'active_workspace_uuid' => $activeWorkspaceUuid,
                'context_workspace_uuid' => $contextWorkspaceUuid,
                'refresh_context' => $manualRefresh,
                'reasons' => array_values(array_filter([
                    $workspaceChanged ? 'context_workspace_mismatch' : null,
                    $manualRefresh ? 'request_refresh_context' : null,
                ])),
            ]);
        }

        return $next($request);
    }
}