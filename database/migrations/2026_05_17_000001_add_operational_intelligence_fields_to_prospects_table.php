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
        Schema::table('prospects', function (Blueprint $table) {
            if (! Schema::hasColumn('prospects', 'status_updated_at')) {
                $table->timestamp('status_updated_at')->nullable()->after('status')->index();
            }

            if (! Schema::hasColumn('prospects', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('status_updated_at')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            if (Schema::hasColumn('prospects', 'last_activity_at')) {
                $table->dropColumn('last_activity_at');
            }

            if (Schema::hasColumn('prospects', 'status_updated_at')) {
                $table->dropColumn('status_updated_at');
            }
        });
    }
};
