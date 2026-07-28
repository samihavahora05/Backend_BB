<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure role exists
        Role::firstOrCreate(['name' => 'super_admin']);

        // Create or Update Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@blueboxx.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );

        $admin->assignRole('super_admin');

        $this->command->info('Admin user seeded! Email: admin@blueboxx.com | Password: password');
    }
}
