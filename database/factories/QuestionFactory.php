<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Simulation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'simulation_id' => Simulation::factory(),
            'prompt' => fake()->sentence(),
            'choices' => ['A' => 'Pilihan A', 'B' => 'Pilihan B', 'C' => 'Pilihan C', 'D' => 'Pilihan D'],
            'correct_answer' => 'A',
            'points' => 1,
            'position' => 1,
        ];
    }
}
