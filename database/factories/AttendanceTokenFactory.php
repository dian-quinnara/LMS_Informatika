<?php

namespace Database\Factories;

use App\Models\AttendanceToken;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttendanceToken>
 */
class AttendanceTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'created_by' => User::factory()->state(['role' => 'admin']),
            'token_hash' => hash_hmac('sha256', Str::upper(Str::random(10)), (string) config('app.key')),
            'starts_at' => now()->subMinute(),
            'expires_at' => now()->addHour(),
        ];
    }
}
