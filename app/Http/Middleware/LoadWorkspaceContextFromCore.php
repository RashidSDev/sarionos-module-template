<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoadWorkspaceContextFromCore
{
    public function handle(
        Request $request,
        Closure $next
    ) {
        $forceRefresh =
            session()->pull(
                'force_refresh_context',
                false
            )
            || session()->pull(
                'force_refresh_users',
                false
            )
            || session()->pull(
                'force_refresh_modules',
                false
            );

        $token = session(
            'sarionos_token'
        );

        if (! $token) {
            return redirect('/logout');
        }

        $coreUrl = rtrim(
            (string) config(
                'sarionos.core_url'
            ),
            '/'
        );

        $moduleKey = trim(
            (string) config(
                'sarionos.module_key'
            )
        );

        $hasCachedContext =
            session()->has(
                'sarionos_modules'
            )
            && session()->has(
                'sarionos_workspace_modules'
            )
            && session()->has(
                'sarionos_module_scope'
            )
            && session()->has(
                'sarionos_user_workspaces'
            )
            && session()->has(
                'sarionos_workspace_users'
            )
            && session()->has(
                'sarionos_navigation_items'
            )
            && session()->has(
                'sarionos_allowed_access_keys'
            )
            && session()->has(
                'sarionos_access_route_rules'
            )
            && session()->has(
                'sarionos_context_version'
            );

        $contextVersionResponse =
            Http::withToken($token)
                ->acceptJson()
                ->timeout(5)
                ->connectTimeout(3)
                ->get(
                    "$coreUrl/api/context/version",
                    [
                        'app_key' =>
                            $moduleKey,
                    ]
                );

        if (
            ! $contextVersionResponse->ok()
        ) {
            if (
                in_array(
                    $contextVersionResponse
                        ->status(),
                    [
                        401,
                        403,
                    ],
                    true
                )
            ) {
                return redirect('/logout');
            }

            if (
                $hasCachedContext
                && ! $forceRefresh
            ) {
                return $next($request);
            }

            abort(
                403,
                'Unable to load context version from Core.'
            );
        }

        $contextVersion =
            (string) $contextVersionResponse
                ->json(
                    'version',
                    ''
                );

        if (
            $hasCachedContext
            && session(
                'sarionos_context_version'
            ) === $contextVersion
            && ! $forceRefresh
        ) {
            /*
             * Cached scope was originally resolved by
             * Core, so Personal/System still shed any
             * workspace context restored by a global
             * workspace cookie.
             */
            if (
                in_array(
                    session(
                        'sarionos_module_scope'
                    ),
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
                    'sarionos_context_workspace_uuid',
                ]);
            }

            return $next($request);
        }

        /*
         * Canonical entitlement catalogue across all
         * three module scopes.
         */
        $modulesResponse =
            Http::withToken($token)
                ->acceptJson()
                ->timeout(5)
                ->connectTimeout(3)
                ->get(
                    "$coreUrl/api/me/modules"
                );

        if (! $modulesResponse->ok()) {
            if (
                in_array(
                    $modulesResponse->status(),
                    [
                        401,
                        403,
                    ],
                    true
                )
            ) {
                return redirect('/logout');
            }

            abort(
                403,
                'Unable to load module catalogue from Core.'
            );
        }

        $modulesJson =
            $modulesResponse->json();

        $normalizedModules =
            $this->normalizeModules(
                $modulesJson[
                    'modules'
                ]
                ?? []
            );

        $currentModule =
            collect(
                $normalizedModules
            )->first(
                fn (array $module): bool =>
                    (
                        $module['key']
                        ?? null
                    ) === $moduleKey
            );

        abort_unless(
            is_array($currentModule),
            403,
            'Module is not entitled.'
        );

        /*
         * Core is the sole authority for the module's
         * effective scope.
         */
        $effectiveScope =
            (string) (
                $currentModule[
                    'scope_type'
                ]
                ?? ''
            );

        abort_unless(
            in_array(
                $effectiveScope,
                [
                    'workspace',
                    'personal',
                    'system',
                ],
                true
            ),
            500,
            'Core returned an invalid module scope.'
        );

        $workspaceUuid =
            $effectiveScope
                === 'workspace'
                    ? (
                        $modulesJson[
                            'workspace_uuid'
                        ]
                        ?? null
                    )
                    : null;

        /*
         * Workspace supplemental context exists only
         * for Workspace-scoped modules.
         */
        $workspacesList = [];
        $workspaceUsers = [];
        $workspaceName = null;
        $isWorkspaceOwner = false;

        if (
            $effectiveScope
                === 'workspace'
        ) {
            abort_if(
                empty($workspaceUuid),
                403,
                'Workspace module requires an active workspace.'
            );

            $workspacesResponse =
                Http::withToken($token)
                    ->acceptJson()
                    ->timeout(5)
                    ->connectTimeout(3)
                    ->get(
                        "$coreUrl/api/me/workspaces"
                    );

            if (! $workspacesResponse->ok()) {
                if (
                    in_array(
                        $workspacesResponse
                            ->status(),
                        [
                            401,
                            403,
                        ],
                        true
                    )
                ) {
                    return redirect('/logout');
                }

                abort(
                    403,
                    'Unable to load workspace catalogue from Core.'
                );
            }

            $workspacesList =
                $workspacesResponse
                    ->json(
                        'workspaces',
                        []
                    )
                ?: [];

            $activeWorkspace =
                collect(
                    $workspacesList
                )->first(
                    fn ($workspace): bool =>
                        is_array($workspace)
                        && (
                            $workspace['uuid']
                            ?? null
                        ) === $workspaceUuid
                );

            if (
                is_array(
                    $activeWorkspace
                )
            ) {
                $workspaceName =
                    $activeWorkspace[
                        'name'
                    ]
                    ?? null;

                $isWorkspaceOwner =
                    (bool) (
                        $activeWorkspace[
                            'is_owner'
                        ]
                        ?? $activeWorkspace[
                            'is_workspace_owner'
                        ]
                        ?? false
                    );
            }

            $usersResponse =
                Http::withToken($token)
                    ->acceptJson()
                    ->timeout(5)
                    ->connectTimeout(3)
                    ->get(
                        "$coreUrl/api/workspaces/"
                        . $workspaceUuid
                        . '/users'
                    );

            if (! $usersResponse->ok()) {
                if (
                    in_array(
                        $usersResponse
                            ->status(),
                        [
                            401,
                            403,
                        ],
                        true
                    )
                ) {
                    return redirect('/logout');
                }

                abort(
                    403,
                    'Unable to load workspace users from Core.'
                );
            }

            $workspaceUsers =
                $usersResponse->json()
                ?: [];
        }

        $navigationResponse =
            Http::withToken($token)
                ->acceptJson()
                ->timeout(5)
                ->connectTimeout(3)
                ->get(
                    "$coreUrl/api/navigation/sidebar",
                    [
                        'app_key' =>
                            $moduleKey,
                    ]
                );

        if (! $navigationResponse->ok()) {
            if (
                in_array(
                    $navigationResponse
                        ->status(),
                    [
                        401,
                        403,
                    ],
                    true
                )
            ) {
                return redirect('/logout');
            }

            abort(
                403,
                'Unable to load navigation from Core.'
            );
        }

        $navigationJson =
            $navigationResponse->json();

        $navigationItems =
            $navigationJson[
                'navigation'
            ]
            ?? [];

        $allowedAccessKeys =
            $navigationJson[
                'allowed_access_keys'
            ]
            ?? [];

        $accessRouteRules =
            $navigationJson[
                'access_route_rules'
            ]
            ?? [];

        /*
         * Core alone decides whether the shared sidebar
         * receives a workspace application catalogue.
         */
        $workspaceModules =
            $navigationJson[
                'workspace_modules'
            ]
            ?? [];

        session()->forget([
            'sarionos_active_workspace_uuid',
            'sarionos_active_workspace_name',
            'sarionos_is_workspace_owner',
        ]);

        if (
            $effectiveScope
                === 'workspace'
        ) {
            session([
                'sarionos_active_workspace_uuid' =>
                    $workspaceUuid,

                'sarionos_active_workspace_name' =>
                    $workspaceName,

                'sarionos_is_workspace_owner' =>
                    $isWorkspaceOwner,
            ]);
        }

        session([
            'sarionos_modules' =>
                $normalizedModules,

            'sarionos_module_scope' =>
                $effectiveScope,

            'sarionos_workspace_modules' =>
                $workspaceModules,

            'sarionos_workspace_users' =>
                $workspaceUsers,

            'sarionos_user_workspaces' =>
                $workspacesList,

            'sarionos_navigation_items' =>
                $navigationItems,

            'sarionos_allowed_access_keys' =>
                $allowedAccessKeys,

            'sarionos_access_route_rules' =>
                $accessRouteRules,

            'sarionos_context_version' =>
                $contextVersion,

            'sarionos_context_workspace_uuid' =>
                $effectiveScope
                    === 'workspace'
                        ? (
                            $workspaceUuid
                            ?: ''
                        )
                        : '',
        ]);

        Log::info(
            '[MODULE][LoadWorkspaceContextFromCore] SCOPE-AWARE CONTEXT STORED',
            [
                'module_key' =>
                    $moduleKey,

                'module_scope' =>
                    $effectiveScope,

                'workspace_uuid' =>
                    $workspaceUuid,

                'modules' =>
                    count(
                        $normalizedModules
                    ),

                'workspace_modules' =>
                    count(
                        $workspaceModules
                    ),

                'workspace_users' =>
                    count(
                        $workspaceUsers
                    ),

                'navigation' =>
                    count(
                        $navigationItems
                    ),

                'context_version' =>
                    $contextVersion,
            ]
        );

        return $next($request);
    }

    /**
     * Preserve Core's shared module presentation and
     * scope contract while tolerating older payloads.
     */
    private function normalizeModules(
        array $modules
    ): array {
        return collect($modules)
            ->filter(
                fn ($module) =>
                    is_array($module)
            )
            ->map(
                function (
                    array $module
                ): array {
                    $normalized = [
                        'uuid' =>
                            $module['uuid']
                            ?? null,

                        'key' =>
                            $module['key']
                            ?? null,

                        'name' =>
                            $module['name']
                            ?? null,

                        'url' =>
                            $module['url']
                            ?? null,

                        'presentation' =>
                            is_array(
                                $module[
                                    'presentation'
                                ]
                                ?? null
                            )
                                ? $module[
                                    'presentation'
                                ]
                                : null,
                    ];

                    if (
                        array_key_exists(
                            'scope_type',
                            $module
                        )
                    ) {
                        $normalized[
                            'scope_type'
                        ] = $module[
                            'scope_type'
                        ];
                    }

                    if (
                        array_key_exists(
                            'personal_availability',
                            $module
                        )
                    ) {
                        $normalized[
                            'personal_availability'
                        ] = $module[
                            'personal_availability'
                        ];
                    }

                    return $normalized;
                }
            )
            ->filter(
                fn (
                    array $module
                ): bool =>
                    ! empty(
                        $module['uuid']
                    )
                    && ! empty(
                        $module['key']
                    )
            )
            ->values()
            ->all();
    }
}
