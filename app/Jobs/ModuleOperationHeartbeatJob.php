<?php

namespace App\Jobs;

use App\Models\ModuleOperation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ModuleOperationHeartbeatJob implements ShouldQueue
{
    use FoundationQueueable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $operationUuid
    ) {
        $this->onQueue((string) config('sarionos.module_key', 'template'));
    }

    public function handle(): void
    {
        $operation = ModuleOperation::where('uuid', $this->operationUuid)->firstOrFail();

        try {
            $operation->forceFill([
                'status' => 'running',
                'progress_current' => 0,
                'progress_total' => 2,
                'progress_percent' => 0,
                'current_step' => config('sarionos.module_name', 'Module') . ' queue heartbeat started.',
                'started_at' => now(),
                'failed_at' => null,
                'error_json' => null,
            ])->save();

            $operation->forceFill([
                'progress_current' => 1,
                'progress_percent' => 50,
                'current_step' => config('sarionos.module_name', 'Module') . ' queue worker picked up the heartbeat job.',
            ])->save();

            $operation->forceFill([
                'status' => 'completed',
                'progress_current' => 2,
                'progress_percent' => 100,
                'current_step' => config('sarionos.module_name', 'Module') . ' queue heartbeat completed.',
                'result_json' => [
                    'completed_at' => now()->toISOString(),
                ],
                'finished_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $operation->forceFill([
                'status' => 'failed',
                'current_step' => config('sarionos.module_name', 'Module') . ' queue heartbeat failed.',
                'error_json' => [
                    'message' => $e->getMessage(),
                    'class' => get_class($e),
                ],
                'failed_at' => now(),
            ])->save();

            throw $e;
        }
    }
}
