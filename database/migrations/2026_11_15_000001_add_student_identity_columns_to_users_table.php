<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NIS siswa (unik) dan penanda wajib ganti kata sandi awal.
     * Kelas siswa memakai kolom users.class_name yang sudah ada.
     * Hanya menambah kolom nullable/default sehingga data lama tetap utuh.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nis', 30)->nullable()->unique()->after('email');
            $table->boolean('must_change_password')->default(false)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nis']);
            $table->dropColumn(['nis', 'must_change_password']);
        });
    }
};
