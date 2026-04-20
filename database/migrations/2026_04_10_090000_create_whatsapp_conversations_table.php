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
        Schema::create('whats_app_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('wa_chat_id', 40)->unique();
            $table->string('prospect_phone', 30)->nullable()->index();
            $table->string('contact_name')->nullable();
            $table->foreignId('prospect_id')->nullable()->constrained('prospects')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->unsignedInteger('unread_for_owner')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['prospect_id', 'last_message_at']);
            $table->index(['owner_id', 'last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_conversations');
    }
};
