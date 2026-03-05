<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;

class CustomPaymentController extends Controller
{
    /**
     * Initiate Bakong payment for an order with enhanced validation
     */
    public function initiateBakongPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Enhanced validation for the order total amount
        if (!$order->total_amount || $order->total_amount <= 0) {
            return response()->json([
                'message' => 'Order total amount is invalid',
                'error' => 'Order total amount must be greater than 0',
                'order_details' => [
                    'id' => $order->id,
                    'total_amount' => $order->total_amount,
                    'payment_status' => $order->payment_status,
                ]
            ], 400);
        }

        // Ensure the amount is a proper numeric value
        $amount = floatval($order->total_amount);
        
        // Validate that the amount is a valid positive number
        if (!is_numeric($amount) || $amount <= 0 || !is_finite($amount)) {
            return response()->json([
                'message' => 'Order total amount is invalid',
                'error' => 'Amount must be a valid positive number',
                'order_details' => [
                    'id' => $order->id,
                    'total_amount' => $order->total_amount,
                    'processed_amount' => $amount,
                ]
            ], 400);
        }

        // Format the amount to ensure it's in proper decimal format
        $formattedAmount = number_format($amount, 2, '.', '');
        $validatedAmount = floatval($formattedAmount);

        // Use the new PaymentService to create Bakong payment
        $paymentService = new PaymentService();
        
        try {
            $result = $paymentService->createBakongPayment($order->id, $validatedAmount, "Order Payment #{$order->id}");

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
        } catch (\Exception $e) {
            Log::error('Custom Bakong payment initiation failed: ' . $e->getMessage());
            Log::error('Order details: ' . json_encode([
                'order_id' => $order->id,
                'total_amount' => $order->total_amount,
                'formatted_amount' => $formattedAmount,
                'validated_amount' => $validatedAmount
            ]));
            
            return response()->json([
                'message' => 'Failed to initiate Bakong payment',
                'error' => $e->getMessage(),
                'order_details' => [
                    'id' => $order->id,
                    'total_amount' => $order->total_amount,
                    'formatted_amount' => $formattedAmount,
                    'validated_amount' => $validatedAmount
                ]
            ], 500);
        }
    }
}