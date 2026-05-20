<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_queue_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('prospects')->cascadeOnDelete();
            $table->string('action_fingerprint', 160);
            $table->string('state', 24)->default('active');
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamp('dismissed_until')->nullable();
            $table->string('reason_tag', 64)->nullable();
            $table->string('reason_note', 255)->nullable();
            $table->foreignId('acted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->unique('lead_id');
            $table->index(['state', 'snoozed_until'], 'lqs_state_snooze_idx');
            $table->index(['state', 'dismissed_until'], 'lqs_state_dismiss_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_queue_states');
    }
};

