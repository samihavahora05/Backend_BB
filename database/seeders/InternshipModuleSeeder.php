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
                'title' => 'Full Stack Web Developer Intern',
                'company_name' => 'Blueboxx Tech Labs',
                'department' => 'Engineering',
                'location' => 'Bangalore, India',
                'mode' => 'Hybrid',
                'duration_months' => 6,
                'duration' => '6 Months',
                'stipend' => 15000,
                'skills_required' => ['React', 'Node.js', 'TypeScript', 'MySQL'],
                'eligibility' => 'B.Tech/BE/MCA Students',
                'description' => 'Build and scale responsive web applications alongside senior full-stack developers.',
                'responsibilities' => 'Write clean frontend components, integrate RESTful APIs, write automated unit tests.',
                'learning_outcomes' => 'Production React/Next.js experience, API optimization, agile workflow.',
                'openings' => 5,
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => Carbon::now()->addMonths(6),
                'application_deadline' => Carbon::now()->addDays(7),
                'status' => 'open',
                'featured' => true,
            ],
            [
                'company_id' => $company->id,
                'title' => 'UI/UX Product Design Intern',
                'company_name' => 'Blueboxx Creative Studio',
                'department' => 'Design',
                'location' => 'Remote',
                'mode' => 'Remote',
                'duration_months' => 3,
                'duration' => '3 Months',
                'stipend' => 12000,
                'skills_required' => ['Figma', 'User Research', 'Prototyping', 'Design Systems'],
                'eligibility' => 'Design graduates or self-taught designers with portfolio',
                'description' => 'Craft elegant, user-centric mobile and web interface prototypes.',
                'responsibilities' => 'Conduct user interviews, design wireframes, iterate on design system components.',
                'learning_outcomes' => 'Master Figma design systems, usability testing, real-client design delivery.',
                'openings' => 3,
                'start_date' => Carbon::now()->addDays(5),
                'end_date' => Carbon::now()->addMonths(3),
                'application_deadline' => Carbon::now()->addDays(4),
                'status' => 'open',
                'featured' => true,
            ],
            [
                'company_id' => $company->id,
                'title' => 'Data Analytics & Insights Intern',
                'company_name' => 'Blueboxx Analytics',
                'department' => 'Data Science',
                'location' => 'Mumbai, India',
                'mode' => 'Onsite',
                'duration_months' => 6,
                'duration' => '6 Months',
                'stipend' => 18000,
                'skills_required' => ['Python', 'SQL', 'PowerBI', 'Pandas'],
                'eligibility' => 'B.Sc/B.Tech/M.Sc in Statistics, CS, or Data Science',
                'description' => 'Analyze large datasets and create executive business dashboards.',
                'responsibilities' => 'Extract data pipelines, build PowerBI visuals, automate analytical reports.',
                'learning_outcomes' => 'Advanced SQL queries, automated ETL pipelines, data-driven strategy.',
                'openings' => 4,
                'start_date' => Carbon::now()->addDays(12),
                'end_date' => Carbon::now()->addMonths(6),
                'application_deadline' => Carbon::now()->addDays(8),
                'status' => 'open',
                'featured' => true,
            ],
            [
                'company_id' => $company->id,
                'title' => 'Digital Marketing & Growth Intern',
                'company_name' => 'Blueboxx Media',
                'department' => 'Marketing',
                'location' => 'Remote',
                'mode' => 'Remote',
                'duration_months' => 3,
                'duration' => '3 Months',
                'stipend' => 10000,
                'skills_required' => ['SEO', 'Content Strategy', 'Google Analytics', 'Social Media'],
                'eligibility' => 'Open to all graduates with strong communication skills',
                'description' => 'Drive organic traffic and run targeted social media acquisition campaigns.',
                'responsibilities' => 'Perform keyword research, create social media assets, track conversions.',
                'learning_outcomes' => 'SEO audit techniques, performance marketing, growth hacking.',
                'openings' => 2,
                'start_date' => Carbon::now()->addDays(3),
                'end_date' => Carbon::now()->addMonths(3),
                'application_deadline' => Carbon::now()->addDays(3),
                'status' => 'open',
                'featured' => false,
            ],
            [
                'company_id' => $company->id,
                'title' => 'Flutter Mobile App Developer Intern',
                'company_name' => 'Blueboxx Mobile Innovations',
                'department' => 'Engineering',
                'location' => 'Vadodara, India',
                'mode' => 'Hybrid',
                'duration_months' => 4,
                'duration' => '4 Months',
                'stipend' => 14000,
                'skills_required' => ['Flutter', 'Dart', 'Firebase', 'State Management'],
                'eligibility' => 'B.Tech/BCA/MCA Students',
                'description' => 'Develop cross-platform iOS and Android applications.',
                'responsibilities' => 'Implement Flutter UI screens, connect Firebase backend, debug app performance.',
                'learning_outcomes' => 'Cross-platform app publishing, state management patterns, push notifications.',
                'openings' => 3,
                'start_date' => Carbon::now()->addDays(7),
                'end_date' => Carbon::now()->addMonths(4),
                'application_deadline' => Carbon::now()->addDays(6),
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
