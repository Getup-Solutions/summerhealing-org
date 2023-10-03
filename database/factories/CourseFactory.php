<?php

namespace Database\Factories;

use App\Models\Agegroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'slug' => fake()->unique()->slug(),
            'thumbnail'=> 'assets/static/img/subscription.png',
            'level' => fake()->randomElement(['Beginner','Medium','Expert']),
            'agegroup_id'=>fake()->randomElement([1,2,3]),
            'excerpt' => fake()->paragraph(),
            'published' => fake()->randomElement([true,false]),
            'description' => fake()->paragraph(),
            'price'=>fake()->randomFloat(1, 20, 30)
        ];
    }
}