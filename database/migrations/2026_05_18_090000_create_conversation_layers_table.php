<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained('prospects')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('channel', 30)->default('whatsapp')->index();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->string('last_message_preview', 255)->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();

            $table->unique(['prospect_id', 'channel']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('wa_message_id', 100)->nullable()->unique();
            $table->string('direction', 20)->index();
            $table->string('message_type', 30)->default('text');
            $table->string('sender_phone', 40)->nullable()->index();
            $table->string('receiver_phone', 40)->nullable()->index();
            $table->text('content')->nullable();
            $table->string('media_url', 1024)->nullable();
            $table->string('status', 30)->nullable()->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('message_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('event_type', 50)->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_events');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
