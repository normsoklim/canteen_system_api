<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\MenuItem;
use App\Models\Category;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_order_with_items()
    {
        // Create a user with customer role
        $user = User::factory()->create([
            'role' => 'customer'
        ]);

        // Create a category
        $category = Category::factory()->create();

        // Create a menu item
        $menuItem = MenuItem::factory()->create([
            'category_id' => $category->id,
            'price' => 10.00
        ]);

        // Authenticate the user
        $this->actingAs($user, 'api');

        // Prepare order data with items
        $orderData = [
            'order_status' => 'pending',
            'user_id' => $user->id,
            'payment_status' => 'unpaid',
            'items' => [
                [
                    'menu_item_id' => $menuItem->id,
                    'quantity' => 2,
                    'unit_price' => 10.00
                ]
            ]
        ];

        // Make the request to create order with items
        $response = $this->postJson('/api/orders-with-items', $orderData);

        // Assert the response
        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Order with items created successfully',
                     'data' => [
                         'order' => [
                             'total_amount' => 20.00,
                             'order_status' => 'pending',
                             'user_id' => $user->id,
                             'payment_status' => 'unpaid'
                         ]
                     ]
                 ]);

        // Verify that the order and order details were created in the database
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total_amount' => 20.00,
            'order_status' => 'pending',
            'payment_status' => 'unpaid'
        ]);

        $this->assertDatabaseHas('order_detail', [
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
            'unit_price' => 10.00,
            'sub_total' => 20.00
        ]);
    }

    public function test_can_get_order_with_items()
    {
        // Create a user with customer role
        $user = User::factory()->create([
            'role' => 'customer'
        ]);

        // Create a category
        $category = Category::factory()->create();

        // Create a menu item
        $menuItem = MenuItem::factory()->create([
            'category_id' => $category->id,
            'price' => 15.00
        ]);

        // Create an order
        $order = \App\Models\Order::create([
            'order_date' => now(),
            'total_amount' => 45.00,
            'order_status' => 'pending',
            'user_id' => $user->id,
            'payment_status' => 'unpaid'
        ]);

        // Create an order detail
        $orderDetail = \App\Models\OrderDetail::create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 3,
            'unit_price' => 15.00,
            'sub_total' => 45.00
        ]);

        // Authenticate the user
        $this->actingAs($user, 'api');

        // Get the order with its details
        $response = $this->getJson('/api/orders/' . $order->id);

        // Assert the response includes order details
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Order retrieved successfully',
                     'data' => [
                         'id' => $order->id,
                         'total_amount' => $order->total_amount,
                         'order_details' => [
                             [
                                 'id' => $orderDetail->id,
                                 'menu_item_id' => $menuItem->id,
                                 'quantity' => 3,
                                 'unit_price' => 15.00,
                                 'sub_total' => 45.00
                             ]
                         ]
                     ]
                 ]);
    }
}