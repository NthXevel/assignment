<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage_products')->except(['index', 'show']);
    }
    
    public function index(Request $request)
    {
        $query = Product::with('category')
                        ->when($request->category, function($q, $category) {
                            $q->whereHas('category', function($cat) use ($category) {
                                $cat->where('slug', $category);
                            });
                        })
                        ->when($request->search, function($q, $search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('model', 'like', "%{$search}%")
                              ->orWhere('sku', 'like', "%{$search}%");
                        });
        
        $products = $query->paginate(15);
        $categories = ProductCategory::all();
        
        return view('products.index', compact('products', 'categories'));
    }
    
    public function create()
    {
        $categories = ProductCategory::all();
        return view('products.create', compact('categories'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'sku' => 'required|string|unique:products',
            'category_id' => 'required|exists:product_categories,id',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'specifications' => 'nullable|array',
        ]);
        
        // Use Factory Method Pattern
        $category = ProductCategory::find($validated['category_id']);
        $factory = ProductFactory::getFactory($category->slug);
        $product = $factory->createProduct($validated);
        
        return redirect()->route('products.show', $product)
                        ->with('success', 'Product created successfully');
    }
    
    public function show(Product $product)
    {
        $product->load('category', 'stocks.branch');
        return view('products.show', compact('product'));
    }
    
    public function edit(Product $product)
    {
        $categories = ProductCategory::all();
        return view('products.edit', compact('product', 'categories'));
    }
    
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'category_id' => 'required|exists:product_categories,id',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'specifications' => 'nullable|array',
        ]);
        
        $product->update($validated);
        
        return redirect()->route('products.show', $product)
                        ->with('success', 'Product updated successfully');
    }
    
    public function destroy(Product $product)
    {
        // Check if product has associated orders or stock
        if ($product->stocks()->sum('quantity') > 0) {
            return back()->with('error', 'Cannot delete product with existing stock');
        }
        
        if ($product->orderItems()->exists()) {
            return back()->with('error', 'Cannot delete product with order history');
        }
        
        $product->delete();
        
        return redirect()->route('products.index')
                        ->with('success', 'Product deleted successfully');
    }
}

