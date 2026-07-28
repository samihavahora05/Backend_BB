<?php

namespace Database\Factories;

use App\Models\Interview;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Interview>
 */
class InterviewFactory extends Factory
{
    protected $model = Interview::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'job_id' => Job::factory(),
            'scheduled_at' => fake()->dateTimeBetween('now', '+14 days'),
            'meeting_url' => 'https://meet.google.com/' . fake()->uuid(),
            'status' => fake()->randomElement(['scheduled', 'completed', 'cancelled']),
        ];
    }
}
