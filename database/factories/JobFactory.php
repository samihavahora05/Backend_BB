<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\CompanyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Senior Frontend Developer', 'Backend PHP Engineer', 
            'Full Stack Developer (Next.js/Laravel)', 'Product Marketing Manager',
            'Data Analyst', 'DevOps Engineer', 'Lead UI Designer'
        ];

        return [
            'job_id_prefix' => 'JOB-2026-' . fake()->unique()->numberBetween(1000, 9999),
            'company_id' => \App\Models\User::factory()->create()->assignRole('company')->id,
            'title' => fake()->randomElement($titles),
            'department' => fake()->randomElement(['Engineering', 'Marketing', 'Design', 'Sales']),
            'industry' => 'Technology',
            'employment_type' => fake()->randomElement(['Full-Time', 'Part-Time', 'Contract']),
            'experience_level' => fake()->randomElement(['Entry-Level', 'Mid-Level', 'Senior']),
            'remote_type' => fake()->randomElement(['Remote', 'Hybrid', 'Onsite']),
            'location' => fake()->city(),
            'salary_min' => fake()->randomFloat(2, 50000, 90000),
            'salary_max' => fake()->randomFloat(2, 95000, 180000),
            'description' => fake()->paragraphs(3, true),
            'vacancies' => fake()->numberBetween(1, 5),
            'application_deadline' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'status' => fake()->randomElement(['active', 'active', 'closed', 'draft']),
        ];
    }
}
