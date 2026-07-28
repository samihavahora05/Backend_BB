<?php

namespace Database\Factories;

use App\Models\JobSeekerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobSeekerProfileFactory extends Factory
{
    protected $model = JobSeekerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone' => fake()->phoneNumber(),
            'resume_path' => 'https://example.com/resumes/' . fake()->uuid() . '.pdf',
            'experience' => fake()->numberBetween(0, 15),
            'expected_salary' => fake()->randomFloat(2, 40000, 150000),
            'preferred_location' => fake()->city(),
            'preferred_job_type' => fake()->randomElement(['Full-time', 'Part-time', 'Remote']),
            'skills' => json_encode(['PHP', 'Laravel', 'React', 'MySQL']),
            'status' => 'active',
        ];
    }
}
