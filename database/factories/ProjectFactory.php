<?php

namespace Database\Factories;

use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'title'        => fake()->sentence(4),
            'description'  => fake()->paragraph(),
            'project_url'  => fake()->url(),
            'archived'     => false,
        ];
    }
}
