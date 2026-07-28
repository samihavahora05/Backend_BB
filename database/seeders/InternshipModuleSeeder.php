<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\InternshipTask;
use App\Models\InternshipSubmission;
use Carbon\Carbon;

class InternshipModuleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::role('admin')->first() ?? User::role('super_admin')->first();
        $student = User::role('student')->first();
        $company = User::role('company')->first();

        // Fallbacks if users don't exist
        if (!$student) {
            $student = User::create([
                'first_name' => 'John',
                'last_name' => 'Doe (Student)',
                'email' => 'student.intern@blueboxx.com',
                'password' => bcrypt('password'),
                'status' => 'active'
            ]);
            $student->assignRole('student');
        }

        if (!$company) {
            $company = User::create([
                'first_name' => 'Acme',
                'last_name' => 'Corp',
                'email' => 'hr.acme@blueboxx.com',
                'password' => bcrypt('password'),
                'status' => 'active'
            ]);
            $company->assignRole('company');
        }

        // 1. Create Internships
        $internships = [
            [
                'company_id' => $company->id,
                'title' => 'Software Engineering Intern',
                'department' => 'Engineering',
                'location' => 'Bangalore, India',
                'mode' => 'Hybrid',
                'duration_months' => 6,
                'duration' => '6 Months',
                'stipend' => 15000,
                'skills_required' => ['Laravel', 'React', 'MySQL'],
                'eligibility' => 'B.Tech/MCA final year students',
                'description' => 'Join our fast-paced engineering team to build scalable web applications.',
                'responsibilities' => 'Write clean code, participate in code reviews, fix bugs.',
                'learning_outcomes' => 'Master MVC architecture, gain hands-on experience with REST APIs.',
                'openings' => 5,
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => Carbon::now()->addMonths(6),
                'application_deadline' => Carbon::now()->addDays(5),
                'status' => 'open',
                'featured' => true,
            ],
            [
                'company_id' => $company->id,
                'title' => 'Product Design Intern',
                'department' => 'Design',
                'location' => 'Remote',
                'mode' => 'Remote',
                'duration_months' => 3,
                'duration' => '3 Months',
                'stipend' => 10000,
                'skills_required' => ['Figma', 'UI/UX', 'Prototyping'],
                'description' => 'Help us design beautiful interfaces.',
                'openings' => 2,
                'status' => 'open',
                'featured' => false,
            ]
        ];

        foreach ($internships as $data) {
            $internship = Internship::create($data);

            // 2. Create Application for first internship
            if ($internship->title === 'Software Engineering Intern') {
                $application = InternshipApplication::create([
                    'internship_id' => $internship->id,
                    'user_id' => $student->id,
                    'status' => 'under_review',
                    'resume_url' => 'https://example.com/resume.pdf',
                    'cover_letter' => 'I am very passionate about Laravel.',
                    'github_url' => 'https://github.com/johndoe',
                ]);

                // 3. Create Tasks
                $task = InternshipTask::create([
                    'internship_id' => $internship->id,
                    'admin_id' => $admin->id,
                    'title' => 'Week 1: Environment Setup',
                    'description' => 'Install Laravel, set up DB, and create basic CRUD.',
                    'type' => 'weekly',
                    'deadline' => Carbon::now()->addDays(7),
                    'max_marks' => 100,
                ]);

                // 4. Create Submission
                InternshipSubmission::create([
                    'task_id' => $task->id,
                    'user_id' => $student->id,
                    'submission_text' => 'I have completed the setup. Here is the github link.',
                    'github_link' => 'https://github.com/johndoe/internship-task-1',
                    'status' => 'pending',
                ]);
            }
        }
    }
}
