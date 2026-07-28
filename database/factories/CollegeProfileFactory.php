<?php

namespace Database\Factories;

use App\Models\CollegeProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CollegeProfile>
 */
class CollegeProfileFactory extends Factory
{
    protected $model = CollegeProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'college_name' => fake()->city() . ' ' . fake()->randomElement(['Institute of Technology', 'University', 'Engineering College', 'Business School']),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'logo' => 'https://ui-avatars.com/api/?name=Uni&background=random',
            'city' => fake()->city(),
            'verification_status' => 'verified',
        ];
    }
}
