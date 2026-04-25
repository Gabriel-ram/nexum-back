<?php

namespace Database\Seeders;

use App\Models\ProjectCategory;
use Illuminate\Database\Seeder;

class ProjectCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Web Development',
            'Mobile Development',
            'Data Science',
            'DevOps',
            'UI/UX Design',
            'Machine Learning',
            'Cybersecurity',
            'Open Source',
            'Other',
        ];

        foreach ($categories as $name) {
            ProjectCategory::firstOrCreate(['name' => $name]);
        }
    }
}
