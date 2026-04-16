<?php

namespace Database\Factories;

use App\Models\Chef;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chef_id'     => Chef::factory(),
            'title'       => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'prep_time'   => fake()->numberBetween(5, 180),
            'difficulty'  => fake()->randomElement(['easy', 'medium', 'hard']),
        ];
    }

    /**
     * State for easy recipes.
     */
    public function easy(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'easy',
            'prep_time'  => fake()->numberBetween(5, 30),
        ]);
    }

    /**
     * State for hard recipes.
     */
    public function hard(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'hard',
            'prep_time'  => fake()->numberBetween(60, 180),
        ]);
    }
}

