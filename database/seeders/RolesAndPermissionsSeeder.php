<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $roles = [
            'super-admin',
            'student',
            'expert',
            'company',
            'college',
            'intern',
            'job-seeker'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Create a default super-admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@blueboxx.in'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'), // default password
            ]
        );
        $admin->assignRole('super-admin');
    }
}
