<?php

namespace App\Http\Controllers;

use App\Jobs\ModuleOperationHeartbeatJob;
use App\Models\ModuleOperation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ModuleSystemCheckController extends Controller
{
    public function index(): View
    {
        abort_unless((bool) session('sarionos_is_workspace_owner', false), 403, 'Only workspace owners can access the module system check.');

        $moduleKey = (string) config('sarionos.module_key', 'template');
        $moduleName = (string) config('sarionos.module_name', 'Template');

        $checks = [];

        $checks[] = $this->check(
            'module_operations table',
            Schema::hasTable('module_operations'),
            'Required for upload/delete/progress tracking.',
            'Run: php artisan migrate'
        );

        $checks[] = $this->check(
            'jobs table',
            Schema::hasTable('jobs'),
            'Required for database queue workers.',
            'Run: php artisan queue:table && php artisan migrate'
        );

        $checks[] = $this->check(
            'failed_jobs table',
            Schema::hasTable('failed_jobs'),
            'Recommended for queue failure diagnostics.',
            'Run: php artisan queue:failed-table && php artisan migrate'
        );

        $checks[] = $this->check(
            'QUEUE_CONNECTION',
            config('queue.default') === 'database',
            'Current value: ' . config('queue.default'),
            'Set QUEUE_CONNECTION=database in .env'
        );

        $checks[] = $this->check(
            $moduleName . ' queue worker',
            $this->queueWorkerLooksActive($moduleKey),
            'Required for async upload/delete operations.',
            'Run: cd ' . base_path() . ' && sudo deployment/scripts/install-module-queue.sh'
        );

        $checks[] = $this->check(
            'Pending queue jobs',
            $this->pendingJobsCount() === 0,
            'Pending jobs: ' . $this->pendingJobsCount(),
            'Start or inspect the queue worker.'
        );

        $latestHeartbeat = $this->latestHeartbeat();

        $checks[] = $this->check(
            'Queue heartbeat execution',
            (bool) ($latestHeartbeat && $latestHeartbeat->status === 'completed'),
            $latestHeartbeat
                ? 'Latest heartbeat: ' . $latestHeartbeat->status . ' at ' . ($latestHeartbeat->updated_at?->format('Y-m-d H:i:s') ?? 'unknown time')
                : 'No heartbeat test has been completed yet.',
            'Click "Run queue heartbeat test" on this page.'
        );

        $checks[] = $this->check(
            'Failed queue jobs',
            $this->failedJobsCount() === 0,
            'Failed jobs: ' . $this->failedJobsCount(),
            'Run: php artisan queue:failed'
        );

        $checks[] = $this->check(
            'storage writable',
            is_writable(storage_path()),
            storage_path(),
            'Fix filesystem permissions for storage/.'
        );

        $checks[] = $this->check(
            'bootstrap/cache writable',
            is_writable(base_path('bootstrap/cache')),
            base_path('bootstrap/cache'),
            'Fix filesystem permissions for bootstrap/cache.'
        );

        $checks[] = $this->check(
            'Core URL configured',
            filled(config('sarionos.core_url')),
            (string) config('sarionos.core_url'),
            'Set SARIONOS_CORE_URL in .env/config.'
        );

        $checks[] = $this->check(
            'Web URL configured',
            filled(config('sarionos.web_url')),
            (string) config('sarionos.web_url'),
            'Set SARIONOS_WEB_URL in .env/config.'
        );

        $checks[] = $this->check(
            'systemd service template',
            file_exists(base_path('deployment/systemd/sarionos-module-queue.service.example')),
            'deployment/systemd/sarionos-module-queue.service.example',
            'Create the deployment systemd template.'
        );

        $checks[] = $this->check(
            'queue installer script',
            file_exists(base_path('deployment/scripts/install-module-queue.sh')),
            'deployment/scripts/install-module-queue.sh',
            'Create the deployment installer script.'
        );

        $failedChecks = collect($checks)
            ->reject(fn (array $check): bool => $check['ok'])
            ->values();

        return view('admin.system-check', [
            'moduleKey' => $moduleKey,
            'moduleName' => $moduleName,
            'checks' => $checks,
            'failedChecks' => $failedChecks,
            'allOk' => $failedChecks->isEmpty(),
            'installCommand' => 'cd ' . base_path() . ' && sudo deployment/scripts/install-module-queue.sh',
            'latestHeartbeat' => $latestHeartbeat,
            'heartbeatAutoRefresh' => $latestHeartbeat && in_array($latestHeartbeat->status, ['queued', 'running'], true),
        ]);
    }

    public function heartbeat(Request $request): RedirectResponse
    {
        abort_unless((bool) session('sarionos_is_workspace_owner', false), 403, 'Only workspace owners can run the module queue heartbeat.');

        $workspaceUuid = session('sarionos_active_workspace_uuid');
        $userUuid = session('sarionos_user_uuid');

        abort_if(empty($workspaceUuid), 403, 'Workspace context missing.');
        abort_if(empty($userUuid), 403, 'User context missing.');
        abort_unless(Schema::hasTable('module_operations'), 500, 'module_operations table is missing. Run migrations first.');

        $operation = ModuleOperation::create([
            'uuid' => (string) Str::uuid(),
            'workspace_uuid' => $workspaceUuid,
            'created_by_user_uuid' => $userUuid,
            'operation_type' => 'queue_heartbeat',
            'status' => 'queued',
            'progress_current' => 0,
            'progress_total' => 2,
            'progress_percent' => 0,
            'current_step' => config('sarionos.module_name', 'Module') . ' queue heartbeat queued.',
            'resource_type' => 'system',
            'resource_uuid' => null,
            'payload_json' => [
                'triggered_from' => 'module_system_check',
            ],
        ]);

        ModuleOperationHeartbeatJob::dispatch($operation->uuid);

        return redirect()
            ->route('template.admin.system-check')
            ->with('status', 'Queue heartbeat test dispatched. Refresh in a few seconds if it is still running.');
    }

    private function check(string $label, bool $ok, string $detail, string $fix): array
    {
        return compact('label', 'ok', 'detail', 'fix');
    }

    private function pendingJobsCount(): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        return (int) DB::table('jobs')->count();
    }

    private function failedJobsCount(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    private function latestHeartbeat(): ?ModuleOperation
    {
        $workspaceUuid = session('sarionos_active_workspace_uuid');
        $userUuid = session('sarionos_user_uuid');

        if (empty($workspaceUuid) || empty($userUuid) || ! Schema::hasTable('module_operations')) {
            return null;
        }

        return ModuleOperation::forWorkspace($workspaceUuid)
            ->where('created_by_user_uuid', $userUuid)
            ->where('operation_type', 'queue_heartbeat')
            ->latest('id')
            ->first();
    }

    private function queueWorkerLooksActive(string $moduleKey): bool
    {
        if (! function_exists('shell_exec')) {
            return false;
        }

        try {
            $base = preg_quote(base_path(), '/');
            $key = preg_quote($moduleKey, '/');
            $output = (string) shell_exec('ps aux | grep -E "queue:work.*' . $key . '|queue:work.*' . $base . '" | grep -v grep');
        } catch (Throwable) {
            return false;
        }

        return trim($output) !== '';
    }
}
