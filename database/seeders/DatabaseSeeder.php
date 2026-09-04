<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database idempotently without destructive wipes or random factory collisions.
     */
    public function run(): void
    {
        // 1. Create Core Roles (if they don't exist yet)
        $roles = ['super_admin', 'admin', 'student', 'expert', 'company', 'college', 'intern', 'job-seeker'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // 2. Create the Super Admin safely
        $admin = User::firstOrCreate(
            ['email' => 'admin@blueboxx.in'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => bcrypt('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('super_admin');

        // 3. Call structured, idempotent baseline seeders
        $this->call([
            PlatformSettingsSeeder::class,
            CmsEcosystemSeeder::class,
            CmsContentSeeder::class,
            ImportCompaniesSeeder::class,
            ImportCollegesSeeder::class,
            ComprehensiveDataSeeder::class,
            JobModuleSeeder::class,
            InternshipModuleSeeder::class,
            StudentModuleSeeder::class,
            InstructorModuleSeeder::class,
            OnlineUniversitiesSeeder::class,
            EventsAndActivitiesSeeder::class,
            CRMAndSalesSeeder::class,
        ]);

        $this->command->info('✅ Database Seeded Successfully with Enterprise Data!');
    }
}