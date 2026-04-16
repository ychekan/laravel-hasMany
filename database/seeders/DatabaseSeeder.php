<?php

namespace Database\Seeders;

use App\Models\Chef;
use App\Models\Recipe;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed chefs and recipes
        $sofia = Chef::create(['name' => 'Sofia Ricci', 'specialty' => 'Italian']);
        $sofia->recipes()->createMany([
            ['title' => 'Cacio e Pepe',  'description' => 'Roman pasta classic',      'prep_time' => 20,  'difficulty' => 'medium'],
            ['title' => 'Tiramisu',      'description' => 'Coffee-soaked dessert',     'prep_time' => 45,  'difficulty' => 'easy'],
            ['title' => 'Osso Buco',     'description' => 'Braised veal shanks',       'prep_time' => 120, 'difficulty' => 'hard'],
            ['title' => 'Panna Cotta',   'description' => 'Silky Italian dessert',     'prep_time' => 30,  'difficulty' => 'easy'],
        ]);

        $kenji = Chef::create(['name' => 'Kenji Mori', 'specialty' => 'Japanese']);
        $kenji->recipes()->createMany([
            ['title' => 'Tonkotsu Ramen', 'description' => 'Rich pork-bone broth ramen', 'prep_time' => 240, 'difficulty' => 'hard'],
            ['title' => 'Gyoza',          'description' => 'Pan-fried pork dumplings',    'prep_time' => 40,  'difficulty' => 'medium'],
            ['title' => 'Tamagoyaki',     'description' => 'Sweet Japanese rolled omelette', 'prep_time' => 15, 'difficulty' => 'easy'],
        ]);

        // Extra random chefs for variety
        Chef::factory()->count(3)->create()->each(function (Chef $chef) {
            Recipe::factory()->count(rand(2, 5))->create(['chef_id' => $chef->id]);
        });
    }
}
