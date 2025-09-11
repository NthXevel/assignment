<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Factories\Products\ProductFactory;
use Illuminate\Support\Facades\DB;

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
            ->where('is_active', true)
            ->when($request->category, function ($q, $category) {
                $q->whereHas('category', function ($cat) use ($category) {
                    $cat->where('slug', $category);
                });
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            });

        $products = $query->paginate(10);
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
            'name' => 'required|string|max:255|unique:products,name',
            'model' => 'required|string|unique:products,model',
            'sku' => 'required|string|unique:products,sku',
            'category_id' => 'required|exists:product_categories,id',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|gt:cost_price',
            'description' => 'nullable|string',
            'specifications' => 'nullable|json', // Change to json validation
        ]);

        try {
            DB::transaction(function () use ($validated, &$product) {
                // Decode specifications JSON if present
                if (!empty($validated['specifications'])) {
                    $validated['specifications'] = json_decode($validated['specifications'], true);
                } else {
                    $validated['specifications'] = [];
                }

                // Create the product
                $product = Product::create([
                    'name' => $validated['name'],
                    'model' => $validated['model'],
                    'sku' => $validated['sku'],
                    'category_id' => $validated['category_id'],
                    'cost_price' => round((float) $validated['cost_price'], 2),
                    'selling_price' => round((float) $validated['selling_price'], 2),
                    'description' => $validated['description'],
                    'specifications' => $validated['specifications'],
                    'is_active' => true
                ]);

                // Create stock entries for all branches with 0 quantity
                $branches = \App\Models\Branch::all();
                foreach ($branches as $branch) {
                    $product->stocks()->create([
                        'branch_id' => $branch->id,
                        'quantity' => 0,
                    ]);
                }
            });

            return redirect()->route('products.show', $product)
                ->with('success', 'Product created successfully');
        } catch (\Exception $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
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
        // Validate input (model & sku not editable)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:product_categories,id',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|gt:cost_price',
            'description' => 'nullable|string',
            'specifications' => 'nullable|array',
            'specifications.key' => 'nullable|array',
            'specifications.value' => 'nullable|array',
            'specifications.key.*' => 'required_with:specifications|string',
            'specifications.value.*' => 'required_with:specifications|string',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            // Convert specifications to associative array
            $specs = [];
            if (!empty($validated['specifications']['key'])) {
                foreach ($validated['specifications']['key'] as $index => $key) {
                    $value = $validated['specifications']['value'][$index] ?? null;
                    if ($key && $value !== null) {
                        $specs[$key] = $value;
                    }
                }
            }

            // Update product fields
            $product->name = $validated['name'];
            $product->category_id = $validated['category_id'];
            $product->cost_price = round((float) $validated['cost_price'], 2);  // safe decimal
            $product->selling_price = round((float) $validated['selling_price'], 2); // safe decimal
            $product->description = $validated['description'] ?? '';
            $product->specifications = $specs; // saved as array (casts to JSON)
            $product->is_active = $validated['is_active'] ?? $product->is_active;

            $product->save();

            return redirect()->route('products.show', $product)
                ->with('success', 'Product updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }




    public function destroy(Product $product)
    {
        DB::transaction(function () use ($product) {
            $totalStock = $product->stocks()->sum('quantity');

            if ($totalStock > 0) {
                $mainBranch = \App\Models\Branch::where('is_main', true)->firstOrFail();

                foreach ($product->stocks as $stock) {
                    if ($stock->branch_id !== $mainBranch->id && $stock->quantity > 0) {

                        // Create a return order
                        $order = \App\Models\Order::create([
                            'order_number' => 'RET-' . strtoupper(uniqid()),
                            'requesting_branch_id' => $stock->branch_id,
                            'supplying_branch_id' => $mainBranch->id,
                            'created_by' => auth()->id(),
                            'status' => 'shipped', // immediately shipped
                            'priority' => 'standard',
                            'notes' => 'Stock return to be discontinued.',
                        ]);

                        // Add order item
                        $order->items()->create([
                            'product_id' => $product->id,
                            'quantity' => $stock->quantity,
                            'unit_price' => $product->selling_price,
                            'total_price' => $product->selling_price * $stock->quantity,
                        ]);

                        // Update main branch stock
                        $mainStock = $product->stocks()
                            ->where('branch_id', $mainBranch->id)
                            ->first();

                        if ($mainStock) {
                            $mainStock->increment('quantity', $stock->quantity);
                        } else {
                            $product->stocks()->create([
                                'branch_id' => $mainBranch->id,
                                'quantity' => $stock->quantity,
                            ]);
                        }

                        // Clear branch stock
                        $stock->update(['quantity' => 0]);
                    }
                }
            }

            // Soft delete product
            $product->update(['is_active' => false]);
        });

        return redirect()->route('products.index')
            ->with('success', 'Product removed successfully. Stock returned to main branch.');
    }


    // Add Category with auto-slug
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name',
            'description' => 'nullable|string',
        ]);

        \App\Models\ProductCategory::create([
            'name' => $validated['name'],
            'slug' => \Illuminate\Support\Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'status' => 'active', // default to active
        ]);

        return redirect()->route('products.index')
            ->with('success', 'Category added successfully');
    }
}
