<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MenuItem;

class MenuItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $menuItems = MenuItem::all();
        return response()->json([
            'data' => $menuItems
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
            $request->validate([
                'item_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'availability_status' => 'required|boolean',
                'image_url' => 'nullable|url',
                'category_id' => 'required|exists:categories,id',
            ]);
    
            $menuItem = MenuItem::create($request->all());
            return response()->json([
                'message' => 'Menu item created successfully',
                'data' => $menuItem
            ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $menuItem = MenuItem::find($id);
        if (!$menuItem) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }
        return response()->json([
            'message' => 'Menu item retrieved successfully',
            'data' => $menuItem
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $menuItem = MenuItem::find($id);
        if (!$menuItem) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        $request->validate([
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'availability_status' => 'required|boolean',
            'image_url' => 'nullable|url',
            'category_id' => 'required|exists:categories,id',
        ]);

        $menuItem->update($request->all());
        return response()->json([
            'message' => 'Menu item updated successfully',
            'data' => $menuItem
        ], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $menuItem = MenuItem::find($id);
        if (!$menuItem) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        $menuItem->delete();
        return response()->json(['message' => 'Menu item deleted successfully'], 204);
    }
}
