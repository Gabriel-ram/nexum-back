<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@portfolio.test'],
            [
                'first_name'        => 'Admin',
                'last_name'         => 'System',
                'password'          => Hash::make(env('ADMIN_PASSWORD', 'Admin1234!')),
                'email_verified_at' => now(),
                'is_active'         => true,
            ]
        );

        $admin->syncRoles(['admin']);
    }
}
