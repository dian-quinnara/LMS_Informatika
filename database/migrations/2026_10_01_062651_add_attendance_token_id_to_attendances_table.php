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
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('attendance_token_id')->nullable()->after('subject_id')->constrained()->restrictOnDelete();
            $table->unique(['user_id', 'attendance_token_id']);
            $table->dropUnique(['user_id', 'subject_id', 'attended_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['user_id', 'subject_id', 'attended_on']);
            $table->dropUnique(['user_id', 'attendance_token_id']);
            $table->dropConstrainedForeignId('attendance_token_id');
        });
    }
};
