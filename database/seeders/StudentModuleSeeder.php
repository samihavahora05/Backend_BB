<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\StudentProfile;
use App\Models\StudentEducation;
use App\Models\StudentSkill;
use App\Models\StudentSocialLink;

class StudentModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Check if student exists
        $studentUser = User::role('student')->first();
        if (!$studentUser) {
            $studentUser = User::factory()->create([
                'first_name' => 'Demo',
                'last_name' => 'Student',
                'email' => 'demo.student@blueboxx.com',
                'password' => bcrypt('password'),
            ]);
            $studentUser->assignRole('student');
        }

        // Attach Profile
        $profile = StudentProfile::updateOrCreate(
            ['user_id' => $studentUser->id],
            [
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'bio' => 'Passionate software developer looking to build great things.',
                'date_of_birth' => '2001-05-15',
                'gender' => 'male',
                'emergency_contact_name' => 'Jane Doe',
                'emergency_contact_phone' => '+91-9876543210'
            ]
        );

        // Attach Education
        StudentEducation::updateOrCreate(
            ['user_id' => $studentUser->id, 'university' => 'Mumbai University'],
            [
                'college_name' => 'KJ Somaiya College of Engineering',
                'course' => 'B.Tech',
                'specialization' => 'Computer Science',
                'semester' => 6,
                'start_year' => 2021,
                'end_year' => 2025,
                'cgpa' => 8.5
            ]
        );

        // Attach Skills
        $skills = ['Laravel', 'React', 'MySQL', 'Python'];
        foreach ($skills as $skill) {
            StudentSkill::updateOrCreate(
                ['user_id' => $studentUser->id, 'skill_name' => $skill],
                ['proficiency' => 'Intermediate']
            );
        }

        // Attach Social Links
        StudentSocialLink::updateOrCreate(
            ['user_id' => $studentUser->id, 'platform' => 'github'],
            ['url' => 'https://github.com/demostudent']
        );
        StudentSocialLink::updateOrCreate(
            ['user_id' => $studentUser->id, 'platform' => 'linkedin'],
            ['url' => 'https://linkedin.com/in/demostudent']
        );
        
        $this->command->info('🎓 Enterprise Student Module Seeded Successfully!');
    }
}
