<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\StudentProfile;
use App\Models\ExpertProfile;
use App\Models\CompanyProfile;
use App\Models\CollegeProfile;
use App\Models\InternProfile;
use App\Models\JobSeekerProfile;
use App\Models\CourseCategory;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Job;
use App\Models\Internship;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Core Roles (if they don't exist yet)
        $roles = ['super_admin', 'admin', 'student', 'expert', 'company', 'college'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // 2. Create the Super Admin safely
        $admin = User::firstOrCreate(
            ['email' => 'admin@blueboxx.in'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]
        );
        $admin->assignRole('super_admin');

        // 3. Seed Students if few exist
        if (User::role('student')->count() < 10) {
            StudentProfile::factory(10)->create()->each(function ($profile) {
                $profile->user->assignRole('student');
            });
        }

        // Seed Companies & Colleges if few exist
        if (CompanyProfile::count() < 5) {
            CompanyProfile::factory(5)->create();
        }
        if (CollegeProfile::count() < 3) {
            CollegeProfile::factory(3)->create();
        }

        // 6. Seed LMS (Courses, Modules, Lessons)
        if (Course::count() < 5) {
            CourseCategory::factory(3)->create()->each(function ($category) {
                Course::factory(2)->create(['category_id' => $category->id])->each(function ($course) {
                    Module::factory(3)->create(['course_id' => $course->id])->each(function ($module) {
                        Lesson::factory(5)->create(['module_id' => $module->id]);
                    });
                });
            });
        }

        // 7. Seed ATS (Jobs & Internships)
        if (Job::count() < 5) {
            Job::factory(10)->create();
        }
        
        $this->call([
            ComprehensiveDataSeeder::class,
            JobModuleSeeder::class,
            InternshipModuleSeeder::class,
            StudentModuleSeeder::class,
            InstructorModuleSeeder::class,
            OnlineUniversitiesSeeder::class,
            ImportCollegesSeeder::class,
            CmsEcosystemSeeder::class,
            CmsContentSeeder::class,
            PlatformSettingsSeeder::class,
            EventsAndActivitiesSeeder::class,
            CRMAndSalesSeeder::class,
        ]);

        $this->command->info('✅ Database Seeded Successfully with Enterprise Data!');
    }
}