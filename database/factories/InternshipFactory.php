<?php

namespace Database\Factories;

use App\Models\Internship;
use App\Models\CompanyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Internship>
 */
class InternshipFactory extends Factory
{
    protected $model = Internship::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Software Engineering Intern', 'Marketing Intern', 
            'Data Science Summer Intern', 'Graphic Design Intern'
        ];

        return [
            'company_profile_id' => CompanyProfile::factory(),
            'category_id' => fake()->numberBetween(1, 10),
            'title' => fake()->randomElement($titles),
            'description' => fake()->paragraphs(3, true),
            'stipend' => fake()->randomFloat(2, 500, 3000), // monthly stipend
            'duration_months' => fake()->randomElement([3, 6, 12]),
            'status' => fake()->randomElement(['open', 'open', 'closed']),
        ];
    }
}
