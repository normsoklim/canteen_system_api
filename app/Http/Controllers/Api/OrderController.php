<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;
use App\Services\OrderPaymentService;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $order = Order::with('user')->get();
        return response()->json([
            'message' => 'Orders retrieved successfully',
            'data' => $order
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'order_status' => 'required|string|max:255',
            'payment_status' => 'required|string|max:255',
            'items' => 'array',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $orderService = new \App\Services\OrderPaymentService();
        
        try {
            $result = $orderService->createOrderWithItems([
                'user_id' => $request->user_id,
                'order_status' => $request->order_status,
                'payment_status' => $request->payment_status,
                'items' => $request->items ?? [],
                'order_date' => now(),
            ]);

            return response()->json([
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $result
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created order with items in storage.
     */
    public function storeWithItems(Request $request)
    {
        $request->validate([
            'order_status' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'payment_status' => 'required|string|max:255',
            'items' => 'required|array',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $orderService = new \App\Services\OrderPaymentService();
        
        try {
            $result = $orderService->createOrderWithItems([
                'user_id' => $request->user_id,
                'order_status' => $request->order_status,
                'payment_status' => $request->payment_status,
                'items' => $request->items,
                'order_date' => now(),
            ]);

            return response()->json([
                'message' => 'Order with items created successfully',
                'data' => [
                    'order' => $result
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create order with items',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $order = Order::with(['user', 'orderDetails.menuItem'])->find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        return response()->json([
            'message' => 'Order retrieved successfully',
            'data' => $order
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $request->validate([
            'order_status' => 'sometimes|string|max:255',
            'user_id' => 'sometimes|exists:users,id',
            'payment_status' => 'sometimes|string|max:255',
            'items' => 'sometimes|array',
            'items.*.menu_item_id' => 'sometimes|exists:menu_items,id',
            'items.*.quantity' => 'sometimes|integer|min:1',
            'items.*.unit_price' => 'sometimes|numeric|min:0',
        ]);

        $orderService = new \App\Services\OrderPaymentService();
        
        try {
            $updatedOrder = $orderService->updateOrder($order, $request->all());
            
            return response()->json([
                'message' => 'Order updated successfully',
                'data' => $updatedOrder
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->delete();
        return response()->json(['message' => 'Order deleted successfully'], 204);
    }
}
