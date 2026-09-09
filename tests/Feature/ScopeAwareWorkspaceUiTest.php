<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScopeAwareWorkspaceUiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sarionos.core_url' =>
                'https://core.example.test',

            'sarionos.self_url' =>
                'https://template.example.test',

            'sarionos.web_url' =>
                'https://web.example.test',

            'sarionos.module_key' =>
                'template',

            'sarionos.module_name' =>
                'Template',
        ]);
    }

    public function test_personal_module_cannot_switch_workspace(): void
    {
        $this->fakeCore(
            scope: 'personal',
            workspaceUuid: null
        );

        $response = $this
            ->authenticatedSession()
            ->post(
                '/workspace/switch',
                [
                    'workspace_uuid' =>
                        '22222222-2222-4222-8222-222222222222',
                ]
            );

        $response->assertNotFound();

        Http::assertNotSent(
            fn ($request): bool =>
                $request->method() === 'POST'
                && $request->url()
                    === 'https://core.example.test/api/me/workspace'
        );
    }

    public function test_system_module_cannot_switch_workspace(): void
    {
        $this->fakeCore(
            scope: 'system',
            workspaceUuid: null
        );

        $response = $this
            ->authenticatedSession()
            ->post(
                '/workspace/switch',
                [
                    'workspace_uuid' =>
                        '22222222-2222-4222-8222-222222222222',
                ]
            );

        $response->assertNotFound();

        Http::assertNotSent(
            fn ($request): bool =>
                $request->method() === 'POST'
                && $request->url()
                    === 'https://core.example.test/api/me/workspace'
        );
    }

    public function test_workspace_module_can_switch_workspace(): void
    {
        $currentWorkspaceUuid =
            '22222222-2222-4222-8222-222222222222';

        $newWorkspaceUuid =
            '33333333-3333-4333-8333-333333333333';

        $this->fakeCore(
            scope: 'workspace',
            workspaceUuid: $currentWorkspaceUuid,
            switchToWorkspaceUuid: $newWorkspaceUuid
        );

        $response = $this
            ->authenticatedSession()
            ->post(
                '/workspace/switch',
                [
                    'workspace_uuid' =>
                        $newWorkspaceUuid,
                ]
            );

        $response->assertRedirect(
            'https://core.example.test/login?redirect='
            . urlencode(
                'https://template.example.test'
                . '/auth/callback?reason=workspace_switched'
            )
        );

        Http::assertSent(
            fn ($request): bool =>
                $request->method() === 'POST'
                && $request->url()
                    === 'https://core.example.test/api/me/workspace'
                && $request[
                    'workspace_uuid'
                ] === $newWorkspaceUuid
        );

        $response->assertSessionHas(
            'sarionos_active_workspace_uuid',
            $newWorkspaceUuid
        );
    }

    public function test_personal_dashboard_does_not_render_workspace_controls(): void
    {
        $this->fakeCore(
            scope: 'personal',
            workspaceUuid: null
        );

        $response = $this
            ->authenticatedSession()
            ->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('Personal')
            ->assertSee('No active workspace is required.')
            ->assertDontSee('Switch workspace')
            ->assertDontSee('Workspace Users');
    }

    public function test_system_dashboard_does_not_render_workspace_controls(): void
    {
        $this->fakeCore(
            scope: 'system',
            workspaceUuid: null
        );

        $response = $this
            ->authenticatedSession()
            ->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('System')
            ->assertDontSee('Switch workspace')
            ->assertDontSee('Workspace Users');
    }

    public function test_workspace_dashboard_renders_workspace_context(): void
    {
        $workspaceUuid =
            '22222222-2222-4222-8222-222222222222';

        $this->fakeCore(
            scope: 'workspace',
            workspaceUuid: $workspaceUuid
        );

        $response = $this
            ->authenticatedSession()
            ->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('Workspace Context')
            ->assertSee('Workspace Users')
            ->assertSee($workspaceUuid);
    }

    public function test_callback_source_does_not_seed_workspace_before_core_scope_resolution(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2)
            . '/app/Http/Controllers/Auth/'
            . 'CallbackController.php'
        );

        $this->assertIsString(
            $source
        );

        $this->assertStringContainsString(
            'The callback establishes authentication only.',
            $source
        );

        $this->assertStringNotContainsString(
            "\$activeWorkspaceUuid =",
            $source
        );

        $this->assertStringNotContainsString(
            "'sarionos_active_workspace_uuid' => \$activeWorkspaceUuid",
            $source
        );
    }

    private function authenticatedSession(): static
    {
        return $this->withSession([
            'sarionos_logged_in' =>
                true,

            'sarionos_token' =>
                'test-token',

            'sarionos_user_uuid' =>
                '11111111-1111-4111-8111-111111111111',

            'sarionos_user_name' =>
                'Test User',

            'sarionos_role_id' =>
                1,
        ]);
    }

    private function fakeCore(
        string $scope,
        ?string $workspaceUuid,
        ?string $switchToWorkspaceUuid = null
    ): void {
        $workspaceList =
            $scope === 'workspace'
                ? [
                    [
                        'uuid' =>
                            $workspaceUuid,

                        'name' =>
                            'Workspace A',

                        'is_owner' =>
                            true,
                    ],

                    [
                        'uuid' =>
                            '33333333-3333-4333-8333-333333333333',

                        'name' =>
                            'Workspace B',

                        'is_owner' =>
                            false,
                    ],
                ]
                : [];

        Http::fake([
            '*/api/sso/validate' =>
                Http::response(
                    [
                        'valid' =>
                            true,
                    ],
                    200
                ),

            'https://core.example.test/api/context/version*' =>
                Http::response(
                    [
                        'version' =>
                            'scope-ui-'
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
                            $workspaceList,
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
                            $scope === 'workspace'
                                ? [
                                    [
                                        'uuid' =>
                                            '44444444-4444-4444-8444-444444444444',

                                        'key' =>
                                            'template',

                                        'name' =>
                                            'Template',
                                    ],
                                ]
                                : [],
                    ],
                    200
                ),

            'https://core.example.test/api/me/workspace' =>
                Http::response(
                    [
                        'workspace_uuid' =>
                            $switchToWorkspaceUuid
                            ?? $workspaceUuid,

                        'workspace_name' =>
                            'Workspace B',
                    ],
                    200
                ),
        ]);
    }
}
