<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda pengumuman yang sudah dibaca siswa, topik materi dan lampiran (PDF/Word/gambar)
 * pada bank soal, serta salinan lampiran pada soal kuis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['announcement_id', 'user_id']);
            $table->index(['user_id', 'announcement_id']);
        });

        Schema::table('bank_questions', function (Blueprint $table) {
            $table->string('topic', 50)->nullable()->after('material_id')->index();
            $table->string('attachment_path')->nullable()->after('points');
            $table->string('attachment_name', 200)->nullable()->after('attachment_path');
            $table->string('attachment_mime', 120)->nullable()->after('attachment_name');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('points');
            $table->string('attachment_name', 200)->nullable()->after('attachment_path');
            $table->string('attachment_mime', 120)->nullable()->after('attachment_name');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_mime']);
        });

        Schema::table('bank_questions', function (Blueprint $table) {
            $table->dropIndex(['topic']);
            $table->dropColumn(['topic', 'attachment_path', 'attachment_name', 'attachment_mime']);
        });

        Schema::dropIfExists('announcement_reads');
    }
};
