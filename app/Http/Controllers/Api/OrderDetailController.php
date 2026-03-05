<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderDetail;

class OrderDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $order_details = OrderDetail::with(['order', 'menuItem'])->get();
        return response()->json([
            'message' => 'Order details retrieved successfully',
            'data' => $order_details
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'menu_item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            
        ]);
        $subTotal = $request->input('quantity') * $request->input('unit_price');
        
        $order_detail = OrderDetail::create([
            'order_id' => $request->input('order_id'),
            'menu_item_id' => $request->input('menu_item_id'),
            'quantity' => $request->input('quantity'),
            'unit_price' => $request->input('unit_price'),
            'sub_total' => $subTotal,
        ]);

        return response()->json([
            'message' => 'Order detail created successfully',
            'data' => $order_detail
        ], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $order_detail = OrderDetail::with(['order', 'menuItem'])->find($id);
        if (!$order_detail) {
            return response()->json([
                'message' => 'Order detail not found'
            ], 404);        
        }
        return response()->json([
            'message' => 'Order detail retrieved successfully',
            'data' => $order_detail
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $order_detail = OrderDetail::find($id);
        if (!$order_detail) {        
            return response()->json([
                'message' => 'Order detail not found'
            ], 404);
        }
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'menu_item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'sub_total' => 'nullable|numeric|min:0',
        ]);
        // Calculate sub_total based on quantity and unit_price if not provided
        $subTotal = $request->input('sub_total');
        if (!$subTotal) {
            $subTotal = $request->input('quantity') * $request->input('unit_price');
        }
        
        $order_detail->update([
            'order_id' => $request->input('order_id'),
            'menu_item_id' => $request->input('menu_item_id'),
            'quantity' => $request->input('quantity'),
            'unit_price' => $request->input('unit_price'),
            'sub_total' => $subTotal,
        ]);
        return response()->json([
            'message' => 'Order detail updated successfully',
            'data' => $order_detail
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $order_detail = OrderDetail::find($id);
        if (!$order_detail) {
            return response()->json([
                'message' => 'Order detail not found'
            ], 404);        
        }
        $order_detail->delete();
        return response()->json([   
            'message' => 'Order detail deleted successfully'
        ], 200);
    }
}
