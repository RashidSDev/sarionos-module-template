<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next)
    {
        $moduleKey = config('sarionos.module_key');

        $modules = collect(session('sarionos_workspace_modules', []));
        $enabled = $modules->contains(
            fn ($m) => ($m['key'] ?? null) === $moduleKey
        );

        if ($enabled) {
            return $next($request);
        }

        session()->forget([
            'sarionos_workspace_users',
            'sarionos_workspace_modules',
            'sarionos_user_workspaces',
            'force_refresh_users',
            'force_refresh_modules',
            'force_refresh_context',
            'sarionos_core_alive_checked_at',
            'sarionos_core_alive_last_ok',
        ]);

        return redirect()->away('https://app.dev.sarionos.com/dashboard?refresh_context=1');
    }
}