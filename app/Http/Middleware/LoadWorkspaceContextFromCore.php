<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoadWorkspaceContextFromCore
{
    public function handle(Request $request, Closure $next)
    {
        $forceRefresh =
            session()->pull('force_refresh_context', false) ||
            session()->pull('force_refresh_users', false) ||
            session()->pull('force_refresh_modules', false);

        $token = session('sarionos_token');
        if (! $token) {
            return redirect('/logout');
        }

        $coreUrl = rtrim(env('SARIONOS_CORE_URL'), '/');

        if (
            session()->has('sarionos_workspace_users') &&
            session()->has('sarionos_workspace_modules') &&
            session()->has('sarionos_user_workspaces') &&
            ! $forceRefresh
        ) {
            return $next($request);
        }

        $workspaceUuid = session('sarionos_active_workspace_uuid');

        if (! $workspaceUuid) {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(5)
                ->connectTimeout(3)
                ->get($coreUrl . '/api/me/workspace');

            if (! $response->ok()) {
                if (in_array($response->status(), [401, 403], true)) {
                    return redirect('/logout');
                }

                abort(403, 'Unable to resolve active workspace.');
            }

            $data = $response->json();

            if (empty($data['workspace_uuid'])) {
                abort(403, 'No active workspace found.');
            }

            session([
                'sarionos_active_workspace_uuid' => $data['workspace_uuid'],
                'sarionos_active_workspace_name' => $data['workspace_name'],
            ]);

            $workspaceUuid = $data['workspace_uuid'];
        }

        $workspacesResponse = Http::withToken($token)
            ->acceptJson()
            ->timeout(5)
            ->connectTimeout(3)
            ->get("$coreUrl/api/me/workspaces");

        if (! $workspacesResponse->ok()) {
            if (in_array($workspacesResponse->status(), [401, 403], true)) {
                return redirect('/logout');
            }

            abort(403, 'Unable to load workspaces list from Core.');
        }

        $usersResponse = Http::withToken($token)
            ->acceptJson()
            ->timeout(5)
            ->connectTimeout(3)
            ->get("$coreUrl/api/workspaces/$workspaceUuid/users");

        if (! $usersResponse->ok()) {
            if (in_array($usersResponse->status(), [401, 403], true)) {
                return redirect('/logout');
            }

            abort(403, 'Unable to load users from Core.');
        }

        $modulesResponse = Http::withToken($token)
            ->acceptJson()
            ->timeout(5)
            ->connectTimeout(3)
            ->get("$coreUrl/api/workspaces/$workspaceUuid/modules");

        if (! $modulesResponse->ok()) {
            if (in_array($modulesResponse->status(), [401, 403], true)) {
                return redirect('/logout');
            }

            abort(403, 'Unable to load modules from Core.');
        }

        $workspacesJson = $workspacesResponse->json();
        $modulesJson    = $modulesResponse->json();

        $workspacesList = $workspacesJson['workspaces'] ?? [];
        $modulesList    = $modulesJson['modules'] ?? [];
        $isOwner        = (bool) ($modulesJson['is_owner'] ?? false);

        session([
            'sarionos_is_workspace_owner' => $isOwner,
            'sarionos_workspace_users'    => $usersResponse->json(),
            'sarionos_workspace_modules'  => collect($modulesList)
                ->map(fn ($m) => [
                    'uuid' => $m['uuid'],
                    'key'  => $m['key'],
                    'url'  => $m['url'],
                ])
                ->values()
                ->all(),
            'sarionos_user_workspaces'    => $workspacesList,
        ]);

        Log::info('[MODULE][LoadWorkspaceContextFromCore] CONTEXT STORED', [
            'workspace'  => $workspaceUuid,
            'owner'      => $isOwner,
            'users'      => count($usersResponse->json()),
            'modules'    => count($modulesList),
            'workspaces' => count($workspacesList),
        ]);

        return $next($request);
    }
}