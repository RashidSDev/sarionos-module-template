<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ModuleOperation extends Model
{
    protected $fillable = [
        'uuid',
        'workspace_uuid',
        'created_by_user_uuid',
        'operation_type',
        'status',
        'progress_current',
        'progress_total',
        'progress_percent',
        'current_step',
        'resource_type',
        'resource_uuid',
        'payload_json',
        'result_json',
        'error_json',
        'started_at',
        'finished_at',
        'failed_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'result_json' => 'array',
        'error_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function scopeForWorkspace(Builder $query, string $workspaceUuid): Builder
    {
        return $query->where('workspace_uuid', $workspaceUuid);
    }
}
