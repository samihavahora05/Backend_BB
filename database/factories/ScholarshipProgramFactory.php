<?php

namespace Database\Factories;

use App\Models\ScholarshipProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScholarshipProgram>
 */
class ScholarshipProgramFactory extends Factory
{
    protected $model = ScholarshipProgram::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true) . ' Merit Scholarship',
            'description' => fake()->paragraphs(2, true),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'deadline' => fake()->dateTimeBetween('now', '+6 months'),
            'status' => fake()->randomElement(['active', 'closed']),
        ];
    }
}
