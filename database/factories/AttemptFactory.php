<?php

namespace Database\Factories;

use App\Models\Attempt;
use App\Models\Simulation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attempt>
 */
class AttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'simulation_id' => Simulation::factory(),
            'started_at' => now(),
            'submitted_at' => null,
            'score' => 0,
            'status' => 'in_progress',
        ];
    }
}
