<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;

class OrderPaymentService
{
    /**
     * Process a complete order with payment
     */
    public function processOrderWithPayment($orderData, $paymentData = null)
    {
        DB::beginTransaction();
        
        try {
            // Create the order with items
            $order = $this->createOrderWithItems($orderData);
            
            // Process payment if provided
            $payment = null;
            if ($paymentData) {
                $payment = $this->processPayment($order, $paymentData);
            }
            
            DB::commit();
            
            return [
                'order' => $order,
                'payment' => $payment
            ];
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    
    /**
     * Create an order with items
     */
    public function createOrderWithItems($orderData)
    {
        $orderData['order_date'] = $orderData['order_date'] ?? now();
        
        // Calculate total amount from items
        $totalAmount = 0;
        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $item) {
                $subTotal = $item['unit_price'] * $item['quantity'];
                $totalAmount += $subTotal;
            }
        }
        
        $order = Order::create([
            'total_amount' => $totalAmount,
            'order_status' => $orderData['order_status'] ?? 'pending',
            'user_id' => $orderData['user_id'],
            'payment_status' => $orderData['payment_status'] ?? 'unpaid',
            'order_date' => $orderData['order_date'],
        ]);
        
        // Create order details
        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $item) {
                $subTotal = $item['unit_price'] * $item['quantity'];
                
                OrderDetail::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'sub_total' => $subTotal,
                ]);
            }
        }
        
        return $order;
    }
    
    /**
     * Process payment for an order
     */
    public function processPayment($order, $paymentData)
    {
        // If amount is not provided, use the order's total amount
        $amount = $paymentData['amount'] ?? $order->total_amount;
        
        // Verify that the payment amount matches the order total if payment status is 'paid'
        if (($paymentData['payment_status'] ?? null) === 'paid' && $amount < $order->total_amount) {
            throw new \Exception('Payment amount is less than order total amount');
        }
        
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_date' => $paymentData['payment_date'] ?? now(),
            'amount' => $amount,
            'payment_method' => $paymentData['payment_method'] ?? 'digital',
            'payment_status' => $paymentData['payment_status'] ?? 'paid',
        ]);
        
        // Update the order's payment status
        $order->update([
            'payment_status' => $paymentData['payment_status'] ?? 'paid'
        ]);
        
        return $payment;
    }
    
    /**
     * Update an order and recalculate total if needed
     */
    public function updateOrder($order, $orderData)
    {
        $order->update($orderData);
        
        // If items are provided, update them and recalculate total
        if (isset($orderData['items'])) {
            // Delete existing order details
            $order->orderDetails()->delete();
            
            // Add new order details
            $totalAmount = 0;
            foreach ($orderData['items'] as $item) {
                $subTotal = $item['unit_price'] * $item['quantity'];
                $totalAmount += $subTotal;
                
                OrderDetail::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'sub_total' => $subTotal,
                ]);
            }
            
            // Update the total amount
            $order->update(['total_amount' => $totalAmount]);
        }
        
        return $order;
    }
    
    /**
     * Refund a payment
     */
    public function refundPayment($payment)
    {
        $payment->update([
            'payment_status' => 'failed'
        ]);
        
        // Update the order's payment status
        $order = $payment->order;
        if ($order) {
            $order->update([
                'payment_status' => 'unpaid'
            ]);
        }
        
        return $payment;
    }
}