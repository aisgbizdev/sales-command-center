<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_queue_action_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('prospects')->cascadeOnDelete();
            $table->foreignId('snapshot_id')->nullable()->constrained('lead_operational_snapshots')->nullOnDelete();
            $table->string('action_fingerprint', 160)->nullable();
            $table->string('action_type', 32);
            $table->string('reason_tag', 64)->nullable();
            $table->string('reason_note', 255)->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('acted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->index(['lead_id', 'acted_at'], 'lqah_lead_acted_idx');
            $table->index(['action_type', 'acted_at'], 'lqah_type_acted_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_queue_action_histories');
    }
};

