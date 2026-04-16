<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chef>
 */
class ChefFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $specialties = ['Italian', 'Japanese', 'French', 'Mexican', 'Indian', 'Pastry', 'Sushi', 'BBQ', 'Vegan'];

        return [
            'name'      => fake()->name(),
            'specialty' => fake()->randomElement($specialties),
        ];
    }
}

