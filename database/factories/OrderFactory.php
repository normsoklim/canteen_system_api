<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_date' => now(),
            'total_amount' => fake()->randomFloat(2, 10, 100),
            'order_status' => 'pending',
            'user_id' => 1, // Default to user ID 1
            'payment_status' => 'unpaid',
        ];
    }
}