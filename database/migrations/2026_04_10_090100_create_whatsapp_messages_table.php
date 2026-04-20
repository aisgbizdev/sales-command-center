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
        Schema::create('whats_app_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('whats_app_conversations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('prospect_id')->nullable()->constrained('prospects')->cascadeOnUpdate()->nullOnDelete();
            $table->string('wa_message_id', 80)->nullable()->unique();
            $table->string('direction', 20)->index();
            $table->string('message_type', 30)->default('text');
            $table->string('from_number', 30)->nullable();
            $table->string('to_number', 30)->nullable();
            $table->text('body')->nullable();
            $table->string('status', 30)->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'sent_at']);
            $table->index(['prospect_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_messages');
    }
};
