<?php

namespace Database\Factories;

use App\Models\InternshipApplication;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InternshipApplication>
 */
class InternshipApplicationFactory extends Factory
{
    protected $model = InternshipApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'internship_id' => Internship::factory(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['applied', 'interviewing', 'hired', 'rejected']),
        ];
    }
}
