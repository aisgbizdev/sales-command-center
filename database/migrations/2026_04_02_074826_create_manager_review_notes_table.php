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
        Schema::create('manager_review_notes', function (Blueprint $table) {
            $table->id();
            $table->text('note');
            $table->string('tag', 30)->default('general')->index();
            $table->foreignId('chat_review_id')->constrained('chat_reviews')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager_review_notes');
    }
};
