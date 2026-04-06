<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->string('user_temperature')->nullable()->after('account_category');
            $table->string('dominant_emotion')->nullable()->after('user_temperature');
            $table->text('main_objection')->nullable()->after('dominant_emotion');
            $table->string('gpt_mode')->nullable()->after('main_objection');
            $table->boolean('bridge_candidate')->default(false)->after('gpt_mode');
            $table->string('bridge_status')->default('none')->after('bridge_candidate');
            $table->string('lost_reason')->nullable()->after('bridge_status');
            $table->dateTime('last_contact_at')->nullable()->after('lost_reason');
        });

        Schema::table('prospect_logs', function (Blueprint $table) {
            $table->boolean('gpt_used')->default(false)->after('result');
            $table->string('gpt_mode')->nullable()->after('gpt_used');
            $table->string('chat_outcome_type')->nullable()->after('gpt_mode');
            $table->text('objection_snapshot')->nullable()->after('chat_outcome_type');
        });
    }

    public function down(): void
    {
        Schema::table('prospect_logs', function (Blueprint $table) {
            $table->dropColumn([
                'gpt_used',
                'gpt_mode',
                'chat_outcome_type',
                'objection_snapshot',
            ]);
        });

        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn([
                'user_temperature',
                'dominant_emotion',
                'main_objection',
                'gpt_mode',
                'bridge_candidate',
                'bridge_status',
                'lost_reason',
                'last_contact_at',
            ]);
        });
    }
};
