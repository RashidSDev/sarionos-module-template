<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_operations')) {
            return;
        }

        if (
            ! Schema::hasColumn(
                'module_operations',
                'scope_type'
            )
        ) {
            Schema::table(
                'module_operations',
                function (Blueprint $table): void {
                    $table
                        ->string(
                            'scope_type',
                            20
                        )
                        ->default(
                            'workspace'
                        )
                        ->index();
                }
            );
        }

        /*
         * Historical template operations were
         * Workspace-scoped because workspace_uuid
         * was previously mandatory.
         */
        DB::table('module_operations')
            ->whereNotNull(
                'workspace_uuid'
            )
            ->update([
                'scope_type' =>
                    'workspace',
            ]);

        /*
         * Personal/System operations intentionally
         * have no workspace ownership boundary.
         */
        Schema::table(
            'module_operations',
            function (Blueprint $table): void {
                $table
                    ->uuid(
                        'workspace_uuid'
                    )
                    ->nullable()
                    ->change();
            }
        );

        Schema::table(
            'module_operations',
            function (Blueprint $table): void {
                $table->index(
                    [
                        'scope_type',
                        'workspace_uuid',
                        'operation_type',
                        'status',
                    ],
                    'module_operations_scope_context_status_idx'
                );
            }
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('module_operations')) {
            return;
        }

        /*
         * Never destroy Personal/System operation data
         * just to make a rollback possible.
         */
        if (
            DB::table('module_operations')
                ->whereNull(
                    'workspace_uuid'
                )
                ->exists()
        ) {
            throw new \RuntimeException(
                'Cannot safely roll back the scope-aware '
                . 'module_operations migration while '
                . 'Personal/System operations exist.'
            );
        }

        Schema::table(
            'module_operations',
            function (Blueprint $table): void {
                $table
                    ->uuid(
                        'workspace_uuid'
                    )
                    ->nullable(false)
                    ->change();
            }
        );

        Schema::table(
            'module_operations',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'module_operations_scope_context_status_idx'
                );

                $table->dropColumn(
                    'scope_type'
                );
            }
        );
    }
};
