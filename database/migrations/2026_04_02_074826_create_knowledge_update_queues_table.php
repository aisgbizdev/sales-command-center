<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('knowledge_update_queues', function (Blueprint $table) {
            $table->id();
            $table->string('priority', 20)->default('normal')->index();
            $table->string('status', 20)->default('queued')->index();
            $table->text('problem_pattern');
            $table->text('recommended_update');
            $table->text('expected_impact')->nullable();
            $table->text('super_admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('chat_review_id')->constrained('chat_reviews')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['priority', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_update_queues');
    }
};
