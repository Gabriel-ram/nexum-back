<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'category_id'  => null,
            'title'        => fake()->sentence(4),
            'description'  => fake()->paragraph(),
            'project_url'  => fake()->url(),
            'technologies' => fake()->randomElements(['PHP', 'Laravel', 'Vue', 'React', 'PostgreSQL', 'Docker'], 3),
        ];
    }
}
