<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class AccessRouteRegistryController extends Controller
{
    public function index()
    {
        $appKey = (string) config('sarionos.module_key', 'template');
        $localRoutes = $this->discoverAccessRoutes();
        $coreRules = $this->fetchCoreAccessRules($appKey);

        $coreByKey = collect($coreRules)
            ->keyBy(fn ($rule) => strtoupper((string) ($rule['method'] ?? '')) . ' ' . trim((string) ($rule['uri'] ?? ''), '/'));

        return view('admin.access-routes.index', compact('appKey', 'localRoutes', 'coreRules', 'coreByKey'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'routes' => ['required', 'array'],
            'routes.*' => ['required', 'string'],
        ]);

        $routes = collect($validated['routes'])
            ->map(fn ($row) => json_decode($row, true))
            ->filter(fn ($row) => is_array($row) && isset($row['method'], $row['uri']))
            ->values()
            ->all();

        if (empty($routes)) {
            return back()->withErrors(['routes' => 'No valid routes selected.']);
        }

        $coreUrl = rtrim((string) config('sarionos.core_url'), '/');
        $internalToken = (string) config('sarionos.internal_service_token', '');

        if ($coreUrl === '' || $internalToken === '') {
            return back()->withErrors(['routes' => 'Core URL or internal service token is missing.']);
        }

        $response = Http::withHeaders([
            'X-SarionOS-Internal-Service-Token' => $internalToken,
        ])->acceptJson()->timeout(15)->post($coreUrl . '/api/access-route-rules/register', [
            'app_key' => (string) config('sarionos.module_key', 'template'),
            'routes' => $routes,
        ]);

        if (! $response->ok()) {
            return back()->withErrors(['routes' => 'Core rejected the route registration.']);
        }

        return redirect()
            ->route('template.admin.access-routes.index')
            ->with('status', 'Selected routes registered with Core.');
    }

    private function discoverAccessRoutes(): array
    {
        return collect(Route::getRoutes())
            ->filter(fn ($route) => in_array('access', $route->gatherMiddleware(), true))
            ->flatMap(function ($route) {
                $uri = trim((string) $route->uri(), '/');

                return collect($route->methods())
                    ->reject(fn ($method) => strtoupper((string) $method) === 'HEAD')
                    ->map(fn ($method) => [
                        'route_name' => $route->getName(),
                        'method' => strtoupper((string) $method),
                        'uri' => $uri,
                        'match_type' => 'path_prefix',
                        'match_value' => explode('/', $uri)[0] ?? $uri,
                    ]);
            })
            ->unique(fn ($route) => $route['method'] . ' ' . $route['uri'])
            ->sortBy(['uri', 'method'])
            ->values()
            ->all();
    }

    private function fetchCoreAccessRules(string $appKey): array
    {
        $coreUrl = rtrim((string) config('sarionos.core_url'), '/');
        $internalToken = (string) config('sarionos.internal_service_token', '');

        if ($coreUrl === '' || $internalToken === '') {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'X-SarionOS-Internal-Service-Token' => $internalToken,
            ])->acceptJson()->timeout(10)->get($coreUrl . '/api/access-route-rules', [
                'app_key' => $appKey,
            ]);
        } catch (\Throwable $e) {
            return [];
        }

        if (! $response->ok()) {
            return [];
        }

        return $response->json('rules', []);
    }
}
