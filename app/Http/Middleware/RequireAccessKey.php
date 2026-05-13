<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAccessKey
{
    public function handle(Request $request, Closure $next)
    {
        $requiredKey = $this->requiredAccessKey($request);

        if ($requiredKey === null) {
            return $next($request);
        }

        $allowedKeys = collect(session('sarionos_allowed_access_keys', []))
            ->filter(fn ($key) => is_string($key))
            ->values();

        if ($allowedKeys->contains($requiredKey)) {
            return $next($request);
        }

        abort(403, 'Access denied.');
    }

    private function requiredAccessKey(Request $request): ?string
    {
        $rules = collect(session('sarionos_access_route_rules', []))
            ->filter(fn ($rule) => is_array($rule) || is_object($rule))
            ->map(fn ($rule) => (array) $rule)
            ->filter(fn ($rule) => ! empty($rule['access_key']))
            ->values();

        if ($rules->isEmpty()) {
            return null;
        }

        $requestMethod = strtoupper($request->method());
        if ($requestMethod === 'HEAD') {
            $requestMethod = 'GET';
        }

        $routeName = (string) optional($request->route())->getName();
        $path = trim($request->path(), '/');

        foreach ($rules as $rule) {
            $ruleMethod = strtoupper((string) ($rule['method'] ?? 'GET'));

            if (! in_array($ruleMethod, ['ANY', $requestMethod], true)) {
                continue;
            }

            $matchType = (string) ($rule['match_type'] ?? 'path_prefix');
            $matchValue = trim((string) ($rule['match_value'] ?? ''), '/');
            $ruleRouteName = (string) ($rule['route_name'] ?? '');

            if ($ruleRouteName !== '' && $routeName !== '' && $routeName === $ruleRouteName) {
                return (string) $rule['access_key'];
            }

            if ($matchType === 'path_exact' && $path === $matchValue) {
                return (string) $rule['access_key'];
            }

            if ($matchType === 'path_prefix' && ($path === $matchValue || str_starts_with($path, $matchValue . '/'))) {
                return (string) $rule['access_key'];
            }

            if ($matchType === 'route_name' && $routeName !== '' && ($routeName === $matchValue || str_starts_with($routeName, rtrim($matchValue, '.') . '.'))) {
                return (string) $rule['access_key'];
            }
        }

        return null;
    }
}
