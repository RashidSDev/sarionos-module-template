<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->uuid('workspace_uuid')->index();
            $table->uuid('created_by_user_uuid')->nullable()->index();

            $table->string('operation_type', 100)->index();
            $table->string('status', 40)->default('queued')->index();

            $table->unsignedInteger('progress_current')->default(0);
            $table->unsignedInteger('progress_total')->default(1);
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('current_step')->nullable();

            $table->string('resource_type', 100)->nullable()->index();
            $table->uuid('resource_uuid')->nullable()->index();

            $table->json('payload_json')->nullable();
            $table->json('result_json')->nullable();
            $table->json('error_json')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();

            $table->index(['workspace_uuid', 'operation_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_operations');
    }
};
