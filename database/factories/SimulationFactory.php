<?php

namespace Database\Factories;

use App\Models\Simulation;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Simulation>
 */
class SimulationFactory extends Factory
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
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'duration_minutes' => 20,
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
