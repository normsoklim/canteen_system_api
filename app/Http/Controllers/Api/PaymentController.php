<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\BakongService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected BakongService $bakongService;

    public function __construct(PaymentService $paymentService, BakongService $bakongService)
    {
        $this->paymentService = $paymentService;
        $this->bakongService = $bakongService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $payments = Payment::with('order')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Payments retrieved successfully',
                'data' => $payments,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving payments: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'payment_method' => 'required|in:cash,digital',
                'payment_status' => 'required|in:paid,failed,pending',
                'amount' => 'nullable|numeric|min:0',
            ]);

            $order = Order::find($validatedData['order_id']);
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Determine payment amount
            $amount = $validatedData['amount'] ?? $order->total_amount;

            // Validate payment amount if status is 'paid'
            if ($validatedData['payment_status'] === 'paid' && $amount < $order->total_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount is less than order total amount',
                ], 400);
            }

            $payment = Payment::create([
                'order_id' => $validatedData['order_id'],
                'payment_date' => now(),
                'amount' => $amount,
                'payment_method' => $validatedData['payment_method'],
                'payment_status' => $validatedData['payment_status'],
            ]);

            // Update order payment status
            $order->update(['payment_status' => $validatedData['payment_status']]);

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully',
                'data' => $payment,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating payment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $payment = Payment::with('order')->find($id);
            
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment retrieved successfully',
                'data' => $payment,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving payment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $payment = Payment::find($id);
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            $validatedData = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'payment_date' => 'required|date',
                'amount' => 'nullable|numeric|min:0',
                'payment_method' => 'required|in:cash,digital',
                'payment_status' => 'required|in:paid,failed,pending',
            ]);

            $payment->update($validatedData);

            // Update the order's payment status
            $order = Order::find($payment->order_id);
            if ($order) {
                $order->update(['payment_status' => $validatedData['payment_status']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => $payment,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating payment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $payment = Payment::find($id);
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting payment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiate Bakong payment for an order
     */
    public function initiateBakongPayment(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'order_id' => 'required|exists:orders,id',
            ]);

            $order = Order::findOrFail($validatedData['order_id']);

            // Validate order total amount
            if (!$order->total_amount || $order->total_amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order total amount is invalid',
                    'error' => 'Order total amount must be greater than 0',
                ], 400);
            }

            $result = $this->paymentService->createBakongPayment(
                $order->id, 
                $order->total_amount, 
                "Order Payment #{$order->id}"
            );

            if ($result) {
                $payment = $result['payment'];

                return response()->json([
                    'success' => true,
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
                    'success' => false,
                    'message' => 'Failed to initiate Bakong payment',
                    'error' => 'Failed to generate QR code',
                    'data' => [
                        'order_id' => $order->id,
                    ]
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error initiating Bakong payment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate Bakong payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify Bakong payment status
     */
    public function verifyBakongPayment(string $id): JsonResponse
    {
        try {
            $result = $this->paymentService->checkPaymentStatus($id);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to check payment status',
                ], 500);
            }

            $payment = $result['payment'];
            
            return response()->json([
                'success' => true,
                'message' => 'Payment status checked successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'payment_status' => $result['status'],
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error verifying Bakong payment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

   /**
    * Get payment history for the authenticated user
    */
   public function getUserPaymentHistory(): JsonResponse
   {
       try {
           $user = auth()->user();
           if (!$user) {
               return response()->json([
                   'success' => false,
                   'message' => 'User not authenticated',
               ], 401);
           }

           $payments = Payment::whereHas('order', function ($query) use ($user) {
               $query->where('user_id', $user->id);
           })
           ->with(['order', 'order.orderDetails'])
           ->orderBy('payment_date', 'desc')
           ->get();

           return response()->json([
               'success' => true,
               'message' => 'Payment history retrieved successfully',
               'data' => $payments,
           ], 200);
       } catch (\Exception $e) {
           Log::error('Error retrieving user payment history: ' . $e->getMessage());
           
           return response()->json([
               'success' => false,
               'message' => 'Failed to retrieve payment history',
               'error' => $e->getMessage(),
           ], 500);
       }
   }

   /**
    * Get order history with payment status for the authenticated user
    */
   public function getUserOrderHistory(): JsonResponse
   {
       try {
           $user = auth()->user();
           if (!$user) {
               return response()->json([
                   'success' => false,
                   'message' => 'User not authenticated',
               ], 401);
           }

           $orders = Order::where('user_id', $user->id)
               ->with(['payment', 'orderDetails', 'user'])
               ->orderBy('order_date', 'desc')
               ->get();

           return response()->json([
               'success' => true,
               'message' => 'Order history retrieved successfully',
               'data' => $orders,
           ], 200);
       } catch (\Exception $e) {
           Log::error('Error retrieving user order history: ' . $e->getMessage());
           
           return response()->json([
               'success' => false,
               'message' => 'Failed to retrieve order history',
               'error' => $e->getMessage(),
           ], 500);
       }
   }

   /**
    * Get specific order with payment details for the authenticated user
    */
   public function getUserOrder(string $id): JsonResponse
   {
       try {
           $user = auth()->user();
           if (!$user) {
               return response()->json([
                   'success' => false,
                   'message' => 'User not authenticated',
               ], 401);
           }

           $order = Order::where('user_id', $user->id)
               ->where('id', $id)
               ->with(['payment', 'orderDetails', 'user'])
               ->first();

           if (!$order) {
               return response()->json([
                   'success' => false,
                   'message' => 'Order not found or does not belong to user',
               ], 404);
           }

           return response()->json([
               'success' => true,
               'message' => 'Order retrieved successfully',
               'data' => $order,
           ], 200);
       } catch (\Exception $e) {
           Log::error('Error retrieving user order: ' . $e->getMessage());
           
           return response()->json([
               'success' => false,
               'message' => 'Failed to retrieve order',
               'error' => $e->getMessage(),
           ], 500);
       }
   }

    /**
     * Handle Bakong payment callback
     */
    public function handleBakongCallback(Request $request): JsonResponse
    {
        try {
            Log::info('Bakong callback received', ['data' => $request->all()]);

            $callbackData = $request->all();
            $result = $this->bakongService->handleCallback($callbackData);

            if ($result['success']) {
                $reference = $result['reference'];
                
                // Find payment by reference
                $payment = Payment::where('payment_reference', $reference)->first();
                
                if ($payment) {
                    $status = $result['status'];
                    
                    // Update payment status based on callback
                    if (in_array($status, ['completed', 'paid', 'success'])) {
                        $payment->update([
                            'payment_status' => 'paid',
                            'payment_date' => now(),
                            'transaction_id' => $result['transaction_id'] ?? null,
                        ]);
                        
                        // Update the associated order
                        $order = $payment->order;
                        if ($order) {
                            $order->update([
                                'payment_status' => 'paid',
                                'order_status' => 'confirmed',
                            ]);
                        }
                    } elseif (in_array($status, ['failed', 'cancelled'])) {
                        $payment->update([
                            'payment_status' => 'failed',
                        ]);
                    }
                }
            }

            // Return success response to acknowledge callback
            return response()->json([
                'success' => true,
                'message' => 'Callback processed successfully',
                'acknowledged' => true,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error handling Bakong callback: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing callback',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify transaction using MD5 hash
     */
    public function verifyTransaction(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'md5' => 'required|string',
            ]);

            $result = $this->bakongService->verifyPayment($validatedData['md5']);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction verified successfully',
                    'data' => $result['data']
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction verification failed',
                    'error' => $result['error'] ?? 'Unknown error',
                    'data' => $result['data'] ?? null
                ], 400);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error verifying Bakong payment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error verifying payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
