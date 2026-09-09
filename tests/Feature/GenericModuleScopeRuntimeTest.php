<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class GenericModuleScopeRuntimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sarionos.core_url' =>
                'https://core.example.test',

            'sarionos.web_url' =>
                'https://web.example.test',

            'sarionos.module_key' =>
                'template',
        ]);

        Route::middleware([
            'web',
            'sync.workspace.from.core',
            'load.workspace.context',
            'ensure.module.enabled',
        ])->get(
            '/__test/module-scope',
            fn () => response()->json([
                'scope' =>
                    session(
                        'sarionos_module_scope'
                    ),

                'workspace_uuid' =>
                    session(
                        'sarionos_active_workspace_uuid'
                    ),

                'workspace_modules' =>
                    session(
                        'sarionos_workspace_modules',
                        []
                    ),
            ])
        );
    }

    public function test_personal_module_works_without_workspace(): void
    {
        $this->fakeCore(
            scope: 'personal',
            workspaceUuid: null,
            workspaceModules: []
        );

        $response = $this
            ->withSession([
                'sarionos_token' =>
                    'test-token',

                'sarionos_user_uuid' =>
                    '11111111-1111-4111-8111-111111111111',
            ])
            ->get(
                '/__test/module-scope'
            );

        $response
            ->assertOk()
            ->assertJson([
                'scope' =>
                    'personal',

                'workspace_uuid' =>
                    null,

                'workspace_modules' =>
                    [],
            ]);

        Http::assertNotSent(
            fn ($request): bool =>
                $request->url()
                    === 'https://core.example.test/api/me/workspaces'
        );

        Http::assertNotSent(
            fn ($request): bool =>
                str_contains(
                    $request->url(),
                    '/api/workspaces/'
                )
        );
    }

    public function test_system_module_works_without_workspace(): void
    {
        $this->fakeCore(
            scope: 'system',
            workspaceUuid: null,
            workspaceModules: []
        );

        $response = $this
            ->withSession([
                'sarionos_token' =>
                    'test-token',

                'sarionos_user_uuid' =>
                    '11111111-1111-4111-8111-111111111111',
            ])
            ->get(
                '/__test/module-scope'
            );

        $response
            ->assertOk()
            ->assertJson([
                'scope' =>
                    'system',

                'workspace_uuid' =>
                    null,

                'workspace_modules' =>
                    [],
            ]);
    }

    public function test_workspace_module_receives_workspace_supplemental_context(): void
    {
        $workspaceUuid =
            '22222222-2222-4222-8222-222222222222';

        $this->fakeCore(
            scope: 'workspace',
            workspaceUuid: $workspaceUuid,
            workspaceModules: [
                [
                    'uuid' =>
                        '33333333-3333-4333-8333-333333333333',
                    'key' =>
                        'template',
                    'name' =>
                        'Template',
                    'scope_type' =>
                        'workspace',
                ],
            ]
        );

        $response = $this
            ->withSession([
                'sarionos_token' =>
                    'test-token',

                'sarionos_user_uuid' =>
                    '11111111-1111-4111-8111-111111111111',
            ])
            ->get(
                '/__test/module-scope'
            );

        $response
            ->assertOk()
            ->assertJson([
                'scope' =>
                    'workspace',

                'workspace_uuid' =>
                    $workspaceUuid,
            ]);

        $response->assertSessionHas(
            'sarionos_user_workspaces'
        );

        $response->assertSessionHas(
            'sarionos_workspace_users'
        );

        Http::assertSent(
            fn ($request): bool =>
                $request->url()
                    === 'https://core.example.test/api/me/workspaces'
        );

        Http::assertSent(
            fn ($request): bool =>
                $request->url()
                    === 'https://core.example.test/api/workspaces/'
                    . $workspaceUuid
                    . '/users'
        );
    }

    public function test_invalid_scope_returned_by_core_fails_closed(): void
    {
        $this->fakeCore(
            scope: 'invalid-scope',
            workspaceUuid: null,
            workspaceModules: []
        );

        $response = $this
            ->withSession([
                'sarionos_token' =>
                    'test-token',

                'sarionos_user_uuid' =>
                    '11111111-1111-4111-8111-111111111111',
            ])
            ->get(
                '/__test/module-scope'
            );

        $response->assertStatus(500);
    }

    private function fakeCore(
        string $scope,
        ?string $workspaceUuid,
        array $workspaceModules
    ): void {
        $workspaces =
            $scope === 'workspace'
                ? [
                    [
                        'uuid' =>
                            $workspaceUuid,
                        'name' =>
                            'Test Workspace',
                        'is_owner' =>
                            true,
                    ],
                ]
                : [];

        Http::fake([
            'https://core.example.test/api/context/version*' =>
                Http::response(
                    [
                        'version' =>
                            'scope-'
                            . $scope,
                    ],
                    200
                ),

            'https://core.example.test/api/me/modules' =>
                Http::response(
                    [
                        'workspace_uuid' =>
                            $workspaceUuid,

                        'modules' => [
                            [
                                'uuid' =>
                                    '44444444-4444-4444-8444-444444444444',

                                'key' =>
                                    'template',

                                'name' =>
                                    'Template',

                                'url' =>
                                    'https://template.example.test',

                                'scope_type' =>
                                    $scope,

                                'personal_availability' =>
                                    $scope === 'personal'
                                        ? 'all_users'
                                        : null,

                                'presentation' =>
                                    null,
                            ],
                        ],
                    ],
                    200
                ),

            'https://core.example.test/api/me/workspaces' =>
                Http::response(
                    [
                        'workspaces' =>
                            $workspaces,
                    ],
                    200
                ),

            'https://core.example.test/api/workspaces/*/users' =>
                Http::response(
                    [
                        [
                            'uuid' =>
                                '55555555-5555-4555-8555-555555555555',

                            'name' =>
                                'Workspace User',
                        ],
                    ],
                    200
                ),

            'https://core.example.test/api/navigation/sidebar*' =>
                Http::response(
                    [
                        'navigation' =>
                            [],

                        'allowed_access_keys' =>
                            [],

                        'access_route_rules' =>
                            [],

                        'workspace_modules' =>
                            $workspaceModules,
                    ],
                    200
                ),
        ]);
    }
}
