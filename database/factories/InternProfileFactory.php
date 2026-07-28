<?php

namespace Database\Factories;

use App\Models\InternProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InternProfile>
 */
class InternProfileFactory extends Factory
{
    protected $model = InternProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'internship_domain' => fake()->randomElement(['Software Engineering', 'Marketing', 'Data Science', 'Design']),
            // Note: assigned_company and mentor_id can be left null initially or updated in the seeder
            'start_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'progress' => fake()->numberBetween(0, 100),
            'attendance' => fake()->numberBetween(80, 100),
            'certificate_status' => fake()->randomElement(['pending', 'issued']),
            'skills' => json_encode(fake()->randomElements(['JavaScript', 'React', 'Node.js', 'Python', 'UI/UX'], 3)),
            'remarks' => fake()->sentence(),
        ];
    }
}
