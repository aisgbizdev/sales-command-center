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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('role')->constrained('units')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->after('unit_id')->constrained('teams')->nullOnDelete();
            $table->index(['role', 'unit_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['team_id']);
            $table->dropIndex(['role', 'unit_id', 'team_id']);
            $table->dropColumn(['unit_id', 'team_id']);
        });
    }
};
