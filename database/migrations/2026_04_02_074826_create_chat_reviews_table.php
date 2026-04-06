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
        Schema::create('chat_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('channel', 30)->default('whatsapp')->index();
            $table->string('customer_name');
            $table->string('customer_company')->nullable();
            $table->string('outcome', 20)->default('netral')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->text('chat_summary');
            $table->longText('chat_excerpt')->nullable();
            $table->text('what_worked')->nullable();
            $table->text('what_failed')->nullable();
            $table->text('suggested_knowledge_update')->nullable();
            $table->foreignId('prospect_id')->nullable()->constrained('prospects')->nullOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['submitted_by', 'outcome', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_reviews');
    }
};
