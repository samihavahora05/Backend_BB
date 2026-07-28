<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ExpertProfile;
use App\Models\ExpertSkill;
use App\Models\ExpertLanguage;
use App\Models\ExpertCertificate;

class InstructorModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Check if demo expert exists
        $expertUser = User::role('expert')->first();
        if (!$expertUser) {
            $expertUser = User::factory()->create([
                'first_name' => 'Demo',
                'last_name' => 'Instructor',
                'email' => 'instructor@blueboxx.com',
                'password' => bcrypt('password'),
            ]);
            $expertUser->assignRole('expert');
        }

        // 2. Attach Profile
        $profile = ExpertProfile::updateOrCreate(
            ['user_id' => $expertUser->id],
            [
                'designation' => 'Lead Web Development Instructor',
                'company' => 'Blueboxx Education',
                'bio' => 'Passionate about teaching and building robust scalable backend systems.',
                'experience_years' => 10,
                'highest_qualification' => 'Master of Computer Applications (MCA)',
                'specialization' => 'Backend Architecture',
                'hourly_rate' => 120.00,
                'is_available' => true,
                'linkedin_url' => 'https://linkedin.com/in/demoinstructor',
                'github_url' => 'https://github.com/demoinstructor',
                
                // Metrics
                'profile_completion_percentage' => 100,
                'average_rating' => 4.9,
                'total_reviews' => 450,
                'total_courses_sold' => 3200,
                'total_students' => 5000,
                'total_certificates_issued' => 1200,
                'total_revenue' => 48500.00,
                'completion_rate' => 85.5,
                'student_satisfaction' => 98.2,
                
                'is_verified' => true,
                'approval_status' => 'approved'
            ]
        );

        // 3. Attach Skills
        $skills = ['Laravel', 'React', 'System Design', 'PHP', 'AWS'];
        foreach ($skills as $skill) {
            ExpertSkill::updateOrCreate(
                ['user_id' => $expertUser->id, 'skill_name' => $skill],
                ['proficiency' => 'Expert']
            );
        }

        // 4. Attach Languages
        ExpertLanguage::updateOrCreate(
            ['user_id' => $expertUser->id, 'language' => 'English'],
            ['proficiency' => 'Fluent']
        );
        ExpertLanguage::updateOrCreate(
            ['user_id' => $expertUser->id, 'language' => 'Hindi'],
            ['proficiency' => 'Native']
        );

        // 5. Attach Certificates
        ExpertCertificate::updateOrCreate(
            ['user_id' => $expertUser->id, 'title' => 'AWS Certified Solutions Architect'],
            [
                'issuer' => 'Amazon Web Services',
                'issue_date' => '2022-05-15',
                'certificate_url' => 'https://aws.amazon.com/certification'
            ]
        );

        $this->command->info('🎓 Enterprise LMS Instructor Module Seeded Successfully!');
    }
}
