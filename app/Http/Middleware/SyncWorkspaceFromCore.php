<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncWorkspaceFromCore
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->boolean('refresh_context')) {
            return $next($request);
        }

        Log::warning('[MODULE][SyncWorkspaceFromCore] TRIGGERED', [
            'url' => $request->fullUrl(),
        ]);

        $token = session('sarionos_token');

        if (! $token) {
            return redirect('/logout');
        }

        $coreUrl = rtrim(env('SARIONOS_CORE_URL'), '/');

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(5)
            ->connectTimeout(3)
            ->get("$coreUrl/api/me/workspace");

        Log::warning('[MODULE][SyncWorkspaceFromCore] CORE RESPONSE', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        if (! $response->ok()) {
            if (in_array($response->status(), [401, 403], true)) {
                return redirect('/logout');
            }

            abort(403, 'Unable to sync workspace from Core.');
        }

        session([
            'sarionos_active_workspace_uuid' => $response['workspace_uuid'],
            'sarionos_active_workspace_name' => $response['workspace_name'],
        ]);

        session()->forget([
            'sarionos_workspace_users',
            'sarionos_workspace_modules',
            'sarionos_user_workspaces',
        ]);

        session()->put('force_refresh_users', true);
        session()->put('force_refresh_modules', true);
        session()->put('force_refresh_context', true);

        return redirect($request->url());
    }
}