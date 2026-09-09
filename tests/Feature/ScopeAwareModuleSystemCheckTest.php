<?php

namespace Tests\Feature;

use App\Http\Controllers\ModuleSystemCheckController;
use App\Jobs\ModuleOperationHeartbeatJob;
use App\Models\ModuleOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ScopeAwareModuleSystemCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        session()->flush();

        Queue::fake();

        config([
            'sarionos.module_name' =>
                'Template',

            'sarionos.module_key' =>
                'template',
        ]);
    }

    public function test_personal_heartbeat_uses_user_boundary_without_workspace(): void
    {
        $userUuid =
            '11111111-1111-4111-8111-111111111111';

        $this->setScopeSession(
            scope: 'personal',
            userUuid: $userUuid
        );

        app(
            ModuleSystemCheckController::class
        )->heartbeat(
            Request::create(
                '/admin/system-check/queue-heartbeat',
                'POST'
            )
        );

        $operation =
            ModuleOperation::query()
                ->latest('id')
                ->firstOrFail();

        $this->assertSame(
            'personal',
            $operation->scope_type
        );

        $this->assertNull(
            $operation->workspace_uuid
        );

        $this->assertSame(
            $userUuid,
            $operation
                ->created_by_user_uuid
        );

        Queue::assertPushed(
            ModuleOperationHeartbeatJob::class
        );
    }

    public function test_workspace_heartbeat_uses_workspace_boundary_without_creator_ownership(): void
    {
        $workspaceUuid =
            '22222222-2222-4222-8222-222222222222';

        $userUuid =
            '11111111-1111-4111-8111-111111111111';

        $this->setScopeSession(
            scope: 'workspace',
            userUuid: $userUuid,
            workspaceUuid: $workspaceUuid,
            workspaceOwner: true
        );

        app(
            ModuleSystemCheckController::class
        )->heartbeat(
            Request::create(
                '/admin/system-check/queue-heartbeat',
                'POST'
            )
        );

        $operation =
            ModuleOperation::query()
                ->latest('id')
                ->firstOrFail();

        $this->assertSame(
            'workspace',
            $operation->scope_type
        );

        $this->assertSame(
            $workspaceUuid,
            $operation->workspace_uuid
        );

        $this->assertSame(
            $userUuid,
            $operation
                ->created_by_user_uuid
        );
    }

    public function test_workspace_system_check_requires_workspace_owner(): void
    {
        $this->setScopeSession(
            scope: 'workspace',
            userUuid:
                '11111111-1111-4111-8111-111111111111',
            workspaceUuid:
                '22222222-2222-4222-8222-222222222222',
            workspaceOwner: false
        );

        try {
            app(
                ModuleSystemCheckController::class
            )->heartbeat(
                Request::create(
                    '/admin/system-check/queue-heartbeat',
                    'POST'
                )
            );

            $this->fail(
                'Expected workspace-owner guard.'
            );
        } catch (HttpException $e) {
            $this->assertSame(
                403,
                $e->getStatusCode()
            );
        }
    }

    public function test_system_heartbeat_requires_user_but_not_workspace(): void
    {
        $userUuid =
            '11111111-1111-4111-8111-111111111111';

        $this->setScopeSession(
            scope: 'system',
            userUuid: $userUuid
        );

        app(
            ModuleSystemCheckController::class
        )->heartbeat(
            Request::create(
                '/admin/system-check/queue-heartbeat',
                'POST'
            )
        );

        $operation =
            ModuleOperation::query()
                ->latest('id')
                ->firstOrFail();

        $this->assertSame(
            'system',
            $operation->scope_type
        );

        $this->assertNull(
            $operation->workspace_uuid
        );

        $this->assertSame(
            $userUuid,
            $operation
                ->created_by_user_uuid
        );
    }

    public function test_invalid_scope_fails_closed(): void
    {
        $this->setScopeSession(
            scope: 'invalid',
            userUuid:
                '11111111-1111-4111-8111-111111111111'
        );

        try {
            app(
                ModuleSystemCheckController::class
            )->heartbeat(
                Request::create(
                    '/admin/system-check/queue-heartbeat',
                    'POST'
                )
            );

            $this->fail(
                'Expected invalid scope rejection.'
            );
        } catch (HttpException $e) {
            $this->assertSame(
                403,
                $e->getStatusCode()
            );
        }
    }

    public function test_personal_latest_heartbeat_is_user_scoped(): void
    {
        $userA =
            '11111111-1111-4111-8111-111111111111';

        $userB =
            '33333333-3333-4333-8333-333333333333';

        $older =
            $this->createHeartbeat(
                scope: 'personal',
                userUuid: $userA
            );

        $this->createHeartbeat(
            scope: 'personal',
            userUuid: $userB
        );

        $latest =
            $this->invokeLatestHeartbeat(
                scope: 'personal',
                userUuid: $userA,
                workspaceUuid: null
            );

        $this->assertSame(
            $older->uuid,
            $latest?->uuid
        );
    }

    public function test_workspace_latest_heartbeat_is_workspace_scoped_not_creator_scoped(): void
    {
        $workspaceUuid =
            '22222222-2222-4222-8222-222222222222';

        $this->createHeartbeat(
            scope: 'workspace',
            userUuid:
                '11111111-1111-4111-8111-111111111111',
            workspaceUuid: $workspaceUuid
        );

        $latestExpected =
            $this->createHeartbeat(
                scope: 'workspace',
                userUuid:
                    '33333333-3333-4333-8333-333333333333',
                workspaceUuid: $workspaceUuid
            );

        $latest =
            $this->invokeLatestHeartbeat(
                scope: 'workspace',
                userUuid:
                    '11111111-1111-4111-8111-111111111111',
                workspaceUuid: $workspaceUuid
            );

        $this->assertSame(
            $latestExpected->uuid,
            $latest?->uuid
        );
    }

    public function test_system_latest_heartbeat_has_no_generic_creator_boundary(): void
    {
        $this->createHeartbeat(
            scope: 'system',
            userUuid:
                '11111111-1111-4111-8111-111111111111'
        );

        $latestExpected =
            $this->createHeartbeat(
                scope: 'system',
                userUuid:
                    '33333333-3333-4333-8333-333333333333'
            );

        $latest =
            $this->invokeLatestHeartbeat(
                scope: 'system',
                userUuid:
                    '11111111-1111-4111-8111-111111111111',
                workspaceUuid: null
            );

        $this->assertSame(
            $latestExpected->uuid,
            $latest?->uuid
        );
    }

    private function setScopeSession(
        string $scope,
        string $userUuid,
        ?string $workspaceUuid = null,
        bool $workspaceOwner = false
    ): void {
        session([
            'sarionos_module_scope' =>
                $scope,

            'sarionos_user_uuid' =>
                $userUuid,

            'sarionos_active_workspace_uuid' =>
                $workspaceUuid,

            'sarionos_is_workspace_owner' =>
                $workspaceOwner,
        ]);
    }

    private function createHeartbeat(
        string $scope,
        string $userUuid,
        ?string $workspaceUuid = null
    ): ModuleOperation {
        return ModuleOperation::create([
            'uuid' =>
                (string) \Illuminate\Support\Str::uuid(),

            'scope_type' =>
                $scope,

            'workspace_uuid' =>
                $workspaceUuid,

            'created_by_user_uuid' =>
                $userUuid,

            'operation_type' =>
                'queue_heartbeat',

            'status' =>
                'completed',

            'progress_current' =>
                2,

            'progress_total' =>
                2,

            'progress_percent' =>
                100,
        ]);
    }

    private function invokeLatestHeartbeat(
        string $scope,
        string $userUuid,
        ?string $workspaceUuid
    ): ?ModuleOperation {
        $controller =
            app(
                ModuleSystemCheckController::class
            );

        $method =
            new ReflectionMethod(
                ModuleSystemCheckController::class,
                'latestHeartbeat'
            );

        $method->setAccessible(
            true
        );

        return $method->invoke(
            $controller,
            $scope,
            $userUuid,
            $workspaceUuid
        );
    }
}
