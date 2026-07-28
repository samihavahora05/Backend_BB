<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLevel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->catchPhrase() . ' Masterclass';
        
        return [
            'title' => $title,
            'slug' => Str::slug($title . '-' . fake()->uuid()),
            'description' => fake()->paragraphs(3, true),
            'category_id' => CourseCategory::factory(), // Will create a new category if none supplied
            'expert_id' => \App\Models\User::factory()->create()->assignRole('expert')->id,
            'level_id' => null,
            'price' => fake()->randomFloat(2, 19.99, 199.99),
            'thumbnail' => 'https://picsum.photos/seed/' . fake()->uuid() . '/800/600',
            'is_published' => fake()->boolean(80), // mostly published
        ];
    }
}
