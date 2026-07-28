<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;

class JobModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure we have at least one company
        $company = User::role('company')->first();
        if (!$company) {
            $company = User::factory()->create([
                'first_name' => 'Demo',
                'last_name' => 'Company',
                'email' => 'hr@democompany.com',
            ]);
            $company->assignRole('company');
        }

        // 2. Ensure we have at least one student (applicant)
        $student = User::role('student')->first();
        if (!$student) {
            $student = User::factory()->create([
                'first_name' => 'John',
                'last_name' => 'Applicant',
                'email' => 'john.applicant@test.com',
            ]);
            $student->assignRole('student');
        }
        
        $admin = User::role('admin')->first() ?? $company; // fallback

        // 3. Create a Demo Job
        $job = Job::create([
            'job_id_prefix' => 'JOB-2026-DEMO',
            'company_id' => $company->id,
            'title' => 'Senior Frontend Developer (Next.js)',
            'department' => 'Engineering',
            'industry' => 'Technology',
            'employment_type' => 'Full-Time',
            'experience_level' => 'Senior',
            'remote_type' => 'Remote',
            'location' => 'New York, USA',
            'salary_min' => 120000,
            'salary_max' => 150000,
            'description' => 'Looking for an expert Next.js developer to lead our frontend team.',
            'responsibilities' => ['Build scalable UIs', 'Mentorship', 'Code Reviews'],
            'requirements' => ['5+ years React', 'Next.js App Router expertise'],
            'benefits' => ['Health Insurance', 'Remote Work', '401k'],
            'required_skills' => ['React', 'Next.js', 'TypeScript', 'TailwindCSS'],
            'vacancies' => 2,
            'application_deadline' => now()->addDays(30),
            'status' => 'active'
        ]);

        // 4. Create an Application in "Interview Scheduled" state
        $application = JobApplication::create([
            'job_id' => $job->id,
            'user_id' => $student->id,
            'resume_path' => 'resumes/demo-resume.pdf',
            'portfolio_url' => 'https://johndoe.dev',
            'github_url' => 'https://github.com/johndoe',
            'linkedin_url' => 'https://linkedin.com/in/johndoe',
            'status' => 'interview_scheduled'
        ]);

        // 5. Schedule an Interview for the application
        $application->interviews()->create([
            'interviewer_id' => $admin->id,
            'round_number' => 1,
            'mode' => 'google_meet',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            'scheduled_at' => now()->addDays(2),
        ]);
        
        // 6. Create another job that is closed
        Job::create([
            'job_id_prefix' => 'JOB-2026-OLD',
            'company_id' => $company->id,
            'title' => 'Junior PHP Developer',
            'employment_type' => 'Full-Time',
            'experience_level' => 'Entry-Level',
            'remote_type' => 'Onsite',
            'location' => 'London, UK',
            'salary_min' => 40000,
            'salary_max' => 55000,
            'description' => 'Great entry level role.',
            'status' => 'closed'
        ]);

        $this->command->info('🏢 Enterprise ATS Jobs Module Seeded Successfully!');
    }
}
