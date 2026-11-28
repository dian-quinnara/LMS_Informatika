<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak belajar siswa untuk menu Beranda & Progres Belajar: materi yang dibuka,
 * simulasi yang dicoba, dan nilai latihan. Satu baris per siswa per aktivitas.
 * Tabel baru saja; tidak mengubah data yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('key', 80);
            $table->string('label');
            $table->string('url', 500)->nullable();
            $table->unsignedInteger('visits')->default(0);
            $table->unsignedTinyInteger('last_score')->nullable();
            $table->unsignedTinyInteger('best_score')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'type', 'key']);
            $table->index(['user_id', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_activities');
    }
};
