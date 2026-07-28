<?php

namespace Database\Factories;

use App\Models\ExpertProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExpertProfile>
 */
class ExpertProfileFactory extends Factory
{
    protected $model = ExpertProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = ['Senior Software Engineer', 'Product Manager', 'Data Scientist', 'Lead UI/UX Designer', 'Cloud Architect'];

        return [
            'user_id' => User::factory(),
            'designation' => fake()->randomElement($titles),
            'company' => fake()->company(),
            'bio' => fake()->paragraphs(2, true),
            'experience_years' => fake()->numberBetween(2, 15),
            'highest_qualification' => fake()->randomElement(["Master's Degree", "Ph.D", "Bachelor's Degree"]),
            'specialization' => 'Software Engineering',
            'hourly_rate' => fake()->randomFloat(2, 50, 250), // Between $50 and $250/hr
            'is_available' => fake()->boolean(90),
            'linkedin_url' => 'https://linkedin.com/in/' . fake()->userName(),
            'github_url' => 'https://github.com/' . fake()->userName(),
            'is_verified' => fake()->boolean(80),
            'approval_status' => fake()->randomElement(['approved', 'approved', 'pending']),
            'profile_completion_percentage' => fake()->numberBetween(60, 100),
            'average_rating' => fake()->randomFloat(2, 3.5, 5.0),
            'total_reviews' => fake()->numberBetween(10, 500),
            'total_courses_sold' => fake()->numberBetween(100, 5000),
            'total_students' => fake()->numberBetween(100, 10000),
            'total_revenue' => fake()->randomFloat(2, 1000, 50000),
        ];
    }
}
