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
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->string('prospect_code')->unique();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('source', 50)->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->unsignedTinyInteger('priority')->default(2);
            $table->decimal('estimation_value', 14, 2)->default(0);
            $table->date('next_follow_up_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
            $table->index(['unit_id', 'team_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
