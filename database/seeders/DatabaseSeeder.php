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
            CategorySeeder::class,
            SkillSeeder::class,
        ]);
    }
}
