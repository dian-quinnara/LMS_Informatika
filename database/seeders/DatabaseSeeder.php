<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Question;
use App\Models\Simulation;
use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(SearchingMaterialSeeder::class);

        $subjects = Subject::factory()->count(3)->create();

        $subjects->each(function (Subject $subject): void {
            Material::factory()->for($subject)->create();
        });

        $simulation = Simulation::factory()->for($subjects->first())->create();
        Question::factory()->count(3)->for($simulation)->sequence(
            ['position' => 1],
            ['position' => 2],
            ['position' => 3],
        )->create();
    }
}
