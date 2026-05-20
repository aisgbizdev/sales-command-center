<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('prospects')->cascadeOnDelete();
            $table->string('event_type', 64);
            $table->dateTime('event_at');
            $table->string('actor_type', 24)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('source', 32)->default('crm');
            $table->string('ref_type', 64)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('dedupe_key', 128)->nullable()->unique();
            $table->timestamps();

            $table->index(['lead_id', 'event_at']);
            $table->index(['event_type', 'event_at']);
            $table->index(['actor_id', 'event_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_timeline_events');
    }
};

