<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'certificate_number' => 'BLU-' . strtoupper(Str::random(10)),
            'file_path' => 'certificates/' . fake()->uuid() . '.pdf',
            'issued_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
