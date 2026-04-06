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
        Schema::create('sales_activities', function (Blueprint $table) {
            $table->id();
            $table->date('activity_date')->index();
            $table->string('activity_type', 30)->index();
            $table->string('summary');
            $table->text('outcome')->nullable();
            $table->date('next_follow_up_date')->nullable()->index();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('sales_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['sales_user_id', 'activity_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_activities');
    }
};
