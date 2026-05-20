<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('prospects')->cascadeOnDelete();
            $table->uuid('request_id')->nullable()->unique();
            $table->unsignedSmallInteger('lead_score')->nullable();
            $table->string('temperature', 24)->nullable();
            $table->string('dominant_emotion', 64)->nullable();
            $table->json('top_objections')->nullable();
            $table->json('next_best_actions')->nullable();
            $table->json('bridge_recommendation')->nullable();
            $table->json('risk_flags')->nullable();
            $table->string('insight_version', 64)->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'generated_at']);
            $table->index(['lead_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_ai_insights');
    }
};

