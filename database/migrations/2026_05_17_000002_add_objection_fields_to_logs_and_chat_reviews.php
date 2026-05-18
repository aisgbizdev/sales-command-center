<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('prospect_logs', 'objection_type')) {
                $table->string('objection_type')->nullable()->after('objection_snapshot')->index();
            }

            if (! Schema::hasColumn('prospect_logs', 'objection_detail')) {
                $table->text('objection_detail')->nullable()->after('objection_type');
            }

            if (! Schema::hasColumn('prospect_logs', 'emotional_state')) {
                $table->string('emotional_state')->nullable()->after('objection_detail')->index();
            }
        });

        Schema::table('chat_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_reviews', 'objection_type')) {
                $table->string('objection_type')->nullable()->after('outcome')->index();
            }

            if (! Schema::hasColumn('chat_reviews', 'objection_detail')) {
                $table->text('objection_detail')->nullable()->after('objection_type');
            }

            if (! Schema::hasColumn('chat_reviews', 'emotional_state')) {
                $table->string('emotional_state')->nullable()->after('objection_detail')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_reviews', function (Blueprint $table) {
            foreach (['emotional_state', 'objection_detail', 'objection_type'] as $column) {
                if (Schema::hasColumn('chat_reviews', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('prospect_logs', function (Blueprint $table) {
            foreach (['emotional_state', 'objection_detail', 'objection_type'] as $column) {
                if (Schema::hasColumn('prospect_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
