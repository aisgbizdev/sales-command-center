<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_operational_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('prospects')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pipeline_stage', 60)->nullable();
            $table->unsignedInteger('priority_score')->default(0);
            $table->string('priority_band', 8)->default('p3');
            $table->unsignedTinyInteger('ghost_risk_score')->default(0);
            $table->unsignedTinyInteger('temperature_score')->default(0);
            $table->unsignedInteger('overdue_minutes')->default(0);
            $table->unsignedInteger('response_delay_minutes')->default(0);
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->unsignedInteger('owner_open_tasks')->default(0);
            $table->string('next_action_code', 80)->nullable();
            $table->decimal('next_action_confidence', 5, 2)->nullable();
            $table->timestamp('next_action_expires_at')->nullable();
            $table->timestamp('stale_after_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique('lead_id');
            $table->index(['owner_user_id', 'priority_score'], 'los_owner_priority_idx');
            $table->index(['priority_band', 'overdue_minutes'], 'los_band_overdue_idx');
            $table->index(['next_action_expires_at', 'priority_score'], 'los_actionexp_priority_idx');
            $table->index('stale_after_at', 'los_stale_after_idx');
            $table->index(['pipeline_stage', 'priority_score'], 'los_stage_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_operational_snapshots');
    }
};
