<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Struktur menu admin: status akun siswa, publikasi & lampiran materi, bank soal,
 * batas percobaan kuis, pengumuman, dan pengaturan sistem.
 *
 * Hanya menambah kolom/tabel baru (nilai default menjaga data lama tetap berlaku):
 * akun lama tetap aktif dan materi lama tetap terbit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->after('sort_order');
            $table->string('attachment_path')->nullable()->after('resource_url');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->string('attachment_mime', 120)->nullable()->after('attachment_name');
        });

        Schema::table('simulations', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_attempts')->nullable()->after('duration_minutes');
        });

        Schema::create('bank_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 10)->index();
            $table->text('prompt');
            $table->json('choices');
            $table->string('correct_answer', 5);
            $table->text('explanation')->nullable();
            $table->unsignedSmallInteger('points')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('bank_question_id')->nullable()->after('simulation_id')->constrained()->nullOnDelete();
            $table->string('category', 10)->nullable()->after('bank_question_id');
            $table->text('explanation')->nullable()->after('correct_answer');
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('class_name', 20)->nullable()->index();
            $table->boolean('is_published')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('announcements');

        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_question_id');
            $table->dropColumn(['category', 'explanation']);
        });

        Schema::dropIfExists('bank_questions');

        Schema::table('simulations', function (Blueprint $table) {
            $table->dropColumn('max_attempts');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['is_published', 'attachment_path', 'attachment_name', 'attachment_mime']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
