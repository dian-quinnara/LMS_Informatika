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
        Schema::table('attendance_tokens', function (Blueprint $table) {
            $table->string('class_name')->nullable()->after('subject_id');
            $table->string('token', 10)->nullable()->unique()->after('token_hash');
            $table->foreignId('subject_id')->nullable()->change();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable(false)->change();
        });

        Schema::table('attendance_tokens', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn(['class_name', 'token']);
            $table->foreignId('subject_id')->nullable(false)->change();
        });
    }
};
