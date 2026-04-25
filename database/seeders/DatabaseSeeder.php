<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            ProfessionalUserSeeder::class,
            SkillSeeder::class,
            ProjectCategorySeeder::class,
            ProjectSeeder::class,      // Depende de SkillSeeder y ProjectCategorySeeder
        ]);
    }
}
