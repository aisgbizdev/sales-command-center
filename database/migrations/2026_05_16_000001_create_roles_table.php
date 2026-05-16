<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label');
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();
        DB::table('roles')->insert([
            [
                'code' => User::ROLE_SUPER_ADMIN,
                'label' => User::ROLE_LABELS[User::ROLE_SUPER_ADMIN],
                'description' => 'Akses penuh sistem.',
                'sort_order' => 10,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => User::ROLE_KEPALA,
                'label' => User::ROLE_LABELS[User::ROLE_KEPALA],
                'description' => 'Melihat semua data unit.',
                'sort_order' => 20,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => User::ROLE_MANAGER,
                'label' => User::ROLE_LABELS[User::ROLE_MANAGER],
                'description' => 'Mengelola data dalam team.',
                'sort_order' => 30,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => User::ROLE_PENJUALAN,
                'label' => User::ROLE_LABELS[User::ROLE_PENJUALAN],
                'description' => 'Input dan kelola prospek sendiri.',
                'sort_order' => 40,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
