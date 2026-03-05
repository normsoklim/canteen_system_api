<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_name' => fake()->word(),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 5, 50),
            'availability_status' => true,
            'image_url' => fake()->url(),
            'category_id' => 1, // Default to category ID 1
        ];
    }
}