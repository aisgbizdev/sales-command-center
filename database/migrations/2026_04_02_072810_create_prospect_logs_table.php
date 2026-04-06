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
        Schema::create('prospect_logs', function (Blueprint $table) {
            $table->id();
            $table->date('log_date')->index();
            $table->string('activity_type', 30)->index();
            $table->string('summary');
            $table->text('result')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->foreignId('prospect_id')->constrained('prospects')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['prospect_id', 'log_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospect_logs');
    }
};
