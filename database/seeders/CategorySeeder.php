<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Web Development',    'slug' => 'web-development'],
            ['name' => 'Mobile Development', 'slug' => 'mobile-development'],
            ['name' => 'Data Science',       'slug' => 'data-science'],
            ['name' => 'DevOps',             'slug' => 'devops'],
            ['name' => 'UI/UX Design',       'slug' => 'ui-ux-design'],
            ['name' => 'Machine Learning',   'slug' => 'machine-learning'],
            ['name' => 'Cybersecurity',      'slug' => 'cybersecurity'],
            ['name' => 'Open Source',        'slug' => 'open-source'],
            ['name' => 'Other',              'slug' => 'other'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
