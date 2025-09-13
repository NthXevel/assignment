<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductApiController extends Controller
{
    public function __construct(private ProductService $service)
    {
        $this->middleware(['auth:sanctum']);
    }

    public function index(Request $request)
    {
        $products = $this->service->list($request->all());
        return response()->json($products);
    }

    public function show(Product $product)
    {
        return response()->json($product->load('category'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|unique:products,model',
            'sku' => 'required|string|unique:products,sku',
            'category_id' => 'required|exists:product_categories,id',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|gt:cost_price',
            'description' => 'nullable|string',
            'specifications' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $product = $this->service->create($validated);
        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|exists:product_categories,id',
            'cost_price' => 'sometimes|required|numeric|min:0',
            'selling_price' => 'sometimes|required|numeric|gt:cost_price',
            'description' => 'nullable|string',
            'specifications' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $updated = $this->service->update($product, $validated);
        return response()->json($updated);
    }

    public function destroy(Product $product)
    {
        $this->service->delete($product);
        return response()->json(['message' => 'Product deactivated']);
    }
}


