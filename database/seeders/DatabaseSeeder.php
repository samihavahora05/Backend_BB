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

        // 2. Create the Super Admin manually
        $admin = User::factory()->create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@blueboxx.in',
            'password' => bcrypt('password'), // password
        ]);
        $admin->assignRole('super_admin');

        // 3. Seed Students
        StudentProfile::factory(20)->create()->each(function ($profile) {
            $profile->user->assignRole('student');
        });

        // Seed Interns & Job Seekers
        InternProfile::factory(10)->create()->each(function ($profile) {
            $profile->user->assignRole('student');
        });
        JobSeekerProfile::factory(10)->create()->each(function ($profile) {
            $profile->user->assignRole('student');
        });

        // NOTE: Instructors (Experts) are created by admin via the Instructors Manager panel.
        // Do NOT auto-seed fake instructors here.

        // 5. Seed Companies & Colleges
        CompanyProfile::factory(10)->create(); // Create 10 companies
        CollegeProfile::factory(5)->create(); // Create 5 colleges

        // 6. Seed LMS (Courses, Modules, Lessons)
        CourseCategory::factory(5)->create()->each(function ($category) {
            // Create 2 courses per category
            Course::factory(2)->create(['category_id' => $category->id])->each(function ($course) {
                // Create 3 modules per course
                Module::factory(3)->create(['course_id' => $course->id])->each(function ($module) {
                    // Create 5 lessons per module
                    Lesson::factory(5)->create(['module_id' => $module->id]);
                });
            });
        });

        // 7. Seed ATS (Jobs & Internships)
        // Create random jobs
        Job::factory(15)->create();
        
        $this->call([
            JobModuleSeeder::class,
            InternshipModuleSeeder::class,
            StudentModuleSeeder::class,
            // InstructorModuleSeeder removed — instructors are created manually by admin
        ]);

        $this->command->info('✅ Database Seeded Successfully with Enterprise Data!');
    }
}
