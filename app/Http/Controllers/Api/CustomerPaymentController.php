<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;

class CustomerPaymentController extends Controller
{
    /**
     * Get customer's payment history (paid and unpaid orders)
     */
    public function getPaymentHistory()
    {
        $user = Auth::user();
        
        // Get all orders for the authenticated user with payment details
        $orders = Order::where('user_id', $user->id)
            ->with(['orderDetails.menuItem', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Separate paid and unpaid orders
        $paidOrders = $orders->filter(function ($order) {
            return $order->payment_status === 'paid';
        });
        
        $unpaidOrders = $orders->filter(function ($order) {
            return $order->payment_status !== 'paid';
        });
        
        return response()->json([
            'message' => 'Payment history retrieved successfully',
            'data' => [
                'paid_orders' => $paidOrders,
                'unpaid_orders' => $unpaidOrders,
            ]
        ], 200);
    }
    
    /**
     * Initiate Bakong payment for an unpaid order
     */
    public function initiateBakongPaymentForOrder(Request $request, $orderId)
    {
        $user = Auth::user();
        
        // Find the order for this user
        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->with('payment')
            ->first();
        
        if (!$order) {
            return response()->json([
                'message' => 'Order not found or does not belong to user',
            ], 404);
        }
        
        // Check if order is already paid
        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Order is already paid',
            ], 400);
        }
        
        // Validate that the order has a valid total amount
        if (!$order->total_amount || $order->total_amount <= 0) {
            return response()->json([
                'message' => 'Order total amount is invalid',
                'error' => 'Order total amount must be greater than 0'
            ], 400);
        }
        
        // Use the PaymentService to create Bakong payment
        $paymentService = new PaymentService();
        $result = $paymentService->createBakongPayment($order->id, $order->total_amount, "Order Payment #{$order->id}");
        
        if ($result) {
            $payment = $result['payment'];
            
            return response()->json([
                'message' => 'Bakong payment initiated successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'amount' => $order->total_amount,
                    'qr_code' => $result['qr_code_image'],
                    'khqr_string' => $result['khqr_string'],
                    'payment_status' => $payment->payment_status,
                    'expires_at' => $payment->expires_at,
                ]
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed to initiate Bakong payment',
                'error' => 'Failed to generate QR code',
                'data' => [
                    'order_id' => $order->id,
                ]
            ], 500);
        }
    }
    
    /**
     * Verify Bakong payment status for an order
     */
    public function verifyBakongPaymentForOrder($paymentId)
    {
        $user = Auth::user();
        
        // Find the payment for this user's order
        $payment = Payment::where('id', $paymentId)
            ->whereHas('order', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();
        
        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found or does not belong to user',
            ], 404);
        }
        
        $paymentService = new PaymentService();
        $result = $paymentService->checkPaymentStatus($paymentId);
        
        $updatedPayment = $result['payment'];
        
        return response()->json([
            'message' => 'Payment status checked successfully',
            'data' => [
                'payment_id' => $updatedPayment->id,
                'order_id' => $updatedPayment->order_id,
                'payment_status' => $result['status'],
                'order_status' => $updatedPayment->order->order_status,
            ]
        ], 200);
    }
    
    /**
     * Get details of a specific order with payment information
     */
    public function getOrderDetails($orderId)
    {
        $user = Auth::user();
        
        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->with(['orderDetails.menuItem', 'payment'])
            ->first();
        
        if (!$order) {
            return response()->json([
                'message' => 'Order not found or does not belong to user',
            ], 404);
        }
        
        return response()->json([
            'message' => 'Order details retrieved successfully',
            'data' => $order
        ], 200);
    }
}