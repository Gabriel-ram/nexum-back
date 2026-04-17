<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SkillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'     => fake()->unique()->word(),
            'type'     => fake()->randomElement(['tecnica', 'blanda', 'project_category']),
            'category' => fake()->word(),
        ];
    }
}
