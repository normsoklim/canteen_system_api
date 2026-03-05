<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\MenuItem;
use App\Models\Category;
use App\Models\Order;

class OrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_order_with_payment()
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

        // Prepare order data with payment
        $orderData = [
            'order_status' => 'pending',
            'user_id' => $user->id,
            'payment_status' => 'paid',
            'items' => [
                [
                    'menu_item_id' => $menuItem->id,
                    'quantity' => 2,
                    'unit_price' => 10.00
                ]
            ],
            'payment_method' => 'digital',
            'payment_amount' => 20.00
        ];

        // Make the request to create order with payment
        $response = $this->postJson('/api/orders-with-payment', $orderData);

        // Assert the response
        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Order with payment created successfully'
                 ]);

        // Verify that the order and payment were created in the database
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total_amount' => 20.00,
            'order_status' => 'pending',
            'payment_status' => 'paid'
        ]);

        $this->assertDatabaseHas('payments', [
            'amount' => 20.00,
            'payment_method' => 'digital',
            'payment_status' => 'paid'
        ]);

        $this->assertDatabaseHas('order_detail', [
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
            'unit_price' => 10.00,
            'sub_total' => 20.00
        ]);
    }

    public function test_order_total_updates_when_order_detail_changes()
    {
        // Create a user with customer role
        $user = User::factory()->create([
            'role' => 'customer'
        ]);

        // Create a category
        $category = Category::factory()->create();

        // Create menu items
        $menuItem1 = MenuItem::factory()->create([
            'category_id' => $category->id,
            'price' => 10.00
        ]);

        $menuItem2 = MenuItem::factory()->create([
            'category_id' => $category->id,
            'price' => 15.00
        ]);

        // Authenticate the user
        $this->actingAs($user, 'api');

        // Create an order with one item
        $orderData = [
            'order_status' => 'pending',
            'user_id' => $user->id,
            'payment_status' => 'unpaid',
            'items' => [
                [
                    'menu_item_id' => $menuItem1->id,
                    'quantity' => 2,
                    'unit_price' => 10.00
                ]
            ]
        ];

        $response = $this->postJson('/api/orders-with-items', $orderData);
        $response->assertStatus(201);

        // Get the created order
        $order = Order::first();

        // Verify initial total
        $this->assertEquals(20.00, $order->refresh()->total_amount);

        // Add another item to the order (this would be done through OrderDetailController in real usage)
        $order->orderDetails()->create([
            'menu_item_id' => $menuItem2->id,
            'quantity' => 1,
            'unit_price' => 15.00,
            'sub_total' => 15.00
        ]);

        // Verify total is updated automatically
        $this->assertEquals(35.00, $order->refresh()->total_amount);
    }

    public function test_payment_updates_order_payment_status()
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

        // Create an order first
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

        $response = $this->postJson('/api/orders-with-items', $orderData);
        $response->assertStatus(201);

        $order = Order::first();

        // Verify initial payment status
        $this->assertEquals('unpaid', $order->refresh()->payment_status);

        // Create a payment for the order
        $paymentData = [
            'order_id' => $order->id,
            'payment_method' => 'digital',
            'payment_status' => 'paid',
            'amount' => 20.00
        ];

        $paymentResponse = $this->postJson('/api/payments', $paymentData);
        $paymentResponse->assertStatus(201);

        // Verify that the order's payment status was updated
        $this->assertEquals('paid', $order->refresh()->payment_status);
    }
}