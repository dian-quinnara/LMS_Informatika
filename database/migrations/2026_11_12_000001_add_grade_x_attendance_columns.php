<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kelas siswa (roster kelas X) dan judul sesi presensi.
     * Hanya menambah kolom nullable sehingga data lama tetap utuh.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('class_name', 20)->nullable()->after('role')->index();
        });

        Schema::table('attendance_tokens', function (Blueprint $table) {
            $table->string('title', 120)->nullable()->after('class_name');
            $table->index('class_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_tokens', function (Blueprint $table) {
            $table->dropIndex(['class_name']);
            $table->dropColumn('title');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['class_name']);
            $table->dropColumn('class_name');
        });
    }
};
