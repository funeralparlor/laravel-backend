<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Get all products
    public function index()
    {
        return response()->json(Product::all());
    }

    // Create product with validation
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'price' => 'required|numeric',
        ]);
    
        // Create the product
        $product = Product::create($validated);
    
        // Return the created product as JSON
        return response()->json($product, 201); // 201 = Created
    }
    // Update product with validation
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|max:255',
            'description' => 'nullable',
            'price' => 'sometimes|numeric'
        ]);

        $product->update($validated);
        return response()->json($product);
    }

    // Delete product
    public function destroy($id)
    {
        Product::destroy($id);
        return response()->json(null, 204);
    }
}