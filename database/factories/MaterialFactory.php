<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
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
            'title' => fake()->sentence(4),
            'summary' => fake()->sentence(),
            'content' => fake()->paragraphs(2, true),
            'resource_url' => null,
            'created_by' => null,
        ];
    }
}
