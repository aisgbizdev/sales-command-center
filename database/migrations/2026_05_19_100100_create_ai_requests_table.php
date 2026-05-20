<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->foreignId('lead_id')->constrained('prospects')->cascadeOnDelete();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('trigger_type', 64);
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->string('payload_hash', 128)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'status']);
            $table->index(['trigger_type', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};

