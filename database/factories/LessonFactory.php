<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['video', 'text', 'quiz']);

        return [
            'module_id' => Module::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'video_url' => $type === 'video' ? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' : null,
            'order' => fake()->numberBetween(1, 20),
            'duration_minutes' => fake()->numberBetween(5, 60),
            'is_free' => fake()->boolean(20),
        ];
    }
}
