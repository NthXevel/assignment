<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Factories\Products\ProductFactory;
use Illuminate\Support\Facades\DB;
use App\Services\StockService;
use App\Services\BranchService;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage_products')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        // Products & Categories are OWNED by Product module -> OK to query directly
        $query = Product::with('category')
            ->when($request->filled('status'), function ($q) use ($request) {
                // support both is_active or status column
                if (\Schema::hasColumn('products', 'is_active')) {
                    $q->where('is_active', $request->status === 'active' ? 1 : 0);
                } elseif (\Schema::hasColumn('products', 'status')) {
                    $q->where('status', $request->status);
                } else {
                    $q->where('is_active', 1);
                }
            }, function ($q) {
                // default: active only
                if (\Schema::hasColumn('products', 'is_active')) {
                    $q->where('is_active', 1);
                } elseif (\Schema::hasColumn('products', 'status')) {
                    $q->where('status', 'active');
                }
            })
            ->when($request->category, function ($q, $category) {
                $q->whereHas('category', fn($cat) => $cat->where('slug', $category));
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        $products   = $query->paginate(10)->withQueryString();
        $categories = ProductCategory::orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = ProductCategory::orderBy('name')->get();

        // Discover available product factories (still local)
        $factories = collect();
        $path = app_path('Factories/Products');
        if (\Illuminate\Support\Facades\File::exists($path)) {
            foreach (\Illuminate\Support\Facades\File::files($path) as $file) {
                $base = $file->getBasename('.php');
                if ($file->getExtension() === 'php' && str_ends_with($base, 'Factory') && $base !== 'ProductFactory') {
                    $factories->push($base);
                }
            }
        }

        return view('products.create', compact('categories', 'factories'));
    }

    public function store(Request $request, BranchService $branchesApi, StockService $stockApi)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255|unique:products,name',
            'model'          => 'required|string|unique:products,model',
            'sku'            => 'required|string|unique:products,sku',
            'category_id'    => 'required|exists:product_categories,id',
            'cost_price'     => 'required|numeric|min:0',
            'selling_price'  => 'required|numeric|gt:cost_price',
            'description'    => 'nullable|string',
            'specifications' => 'nullable',
            'factory_class'  => 'nullable|string',
        ]);

        try {
            $product = null;

            DB::transaction(function () use ($validated, &$product) {
                $category = ProductCategory::findOrFail($validated['category_id']);

                // Resolve factory: user-selected or category-mapped
                $factory = null;
                if (!empty($validated['factory_class'])) {
                    $fqcn = "App\\Factories\\Products\\{$validated['factory_class']}";
                    if (class_exists($fqcn)) {
                        $instance = new $fqcn();
                        if ($instance instanceof ProductFactory) {
                            $factory = $instance;
                        }
                    }
                }
                if (!$factory) {
                    $factory = $category->getFactoryInstance();
                }

                // Normalize specifications to array
                $specs = [];
                if (!empty($validated['specifications'])) {
                    if (is_string($validated['specifications'])) {
                        $decoded = json_decode($validated['specifications'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $specs = $decoded;
                        }
                    } elseif (is_array($validated['specifications'])) {
                        $specs = $validated['specifications'];
                    }
                }

                $data = [
                    'name'           => $validated['name'],
                    'model'          => $validated['model'],
                    'sku'            => $validated['sku'],
                    'category_id'    => $validated['category_id'],
                    'cost_price'     => round((float)$validated['cost_price'], 2),
                    'selling_price'  => round((float)$validated['selling_price'], 2),
                    'description'    => $validated['description'] ?? '',
                    'specifications' => $specs,
                ];
                if (\Schema::hasColumn('products', 'is_active')) $data['is_active'] = true;
                elseif (\Schema::hasColumn('products', 'status')) $data['status'] = 'active';

                $product = $factory->createProduct($data);
            });

            try {
                $branches = $branchesApi->listActive(); // [{id,name,status}, ...]
            } catch (\Throwable $e) {
                $branches = [];
            }

            foreach ($branches as $b) {
                try {
                    $stockApi->upsert((int)$b['id'], (int)$product->id, 0, 0, true); // quantity=0, keep existing
                } catch (\Throwable $e) {
                    // may log this, but don't block product creation (So currently, don't log first - by Jie Han)
                    // \Log::warning('Stock upsert failed', ['branch_id'=>$b['id'],'product_id'=>$product->id,'err'=>$e->getMessage()]);
                }
            }

            return redirect()->route('products.show', $product)
                ->with('success', 'Product created successfully');
        } catch (\Throwable $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    public function show(Product $product, StockService $stockApi)
    {
        // Categories belong to Product module -> OK to eager-load
        $product->load('category');

        // Get per-branch availability via REST (no DB join to stocks/branches)
        try {
            $availability = $stockApi->availability((int)$product->id, null); // include all branches
        } catch (\Throwable $e) {
            $availability = []; // view can show a “unavailable” note
        }

        return view('products.show', compact('product', 'availability'));
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::orderBy('name')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'category_id'            => 'required|exists:product_categories,id',
            'cost_price'             => 'required|numeric|min:0',
            'selling_price'          => 'required|numeric|gt:cost_price',
            'description'            => 'nullable|string',
            // accept either JSON string OR key/value arrays from the form
            'specifications'         => 'nullable',
            'specifications.key'     => 'nullable|array',
            'specifications.value'   => 'nullable|array',
            'specifications.key.*'   => 'nullable|string',
            'specifications.value.*' => 'nullable|string',
            'is_active'              => 'nullable|boolean',
            'status'                 => 'nullable|string|in:active,inactive',
        ]);

        try {
            // Normalize specs
            $specs = [];
            if (isset($validated['specifications'])) {
                if (is_array($validated['specifications']) && isset($validated['specifications']['key'])) {
                    foreach ($validated['specifications']['key'] as $i => $k) {
                        $v = $validated['specifications']['value'][$i] ?? null;
                        if ($k !== null && $k !== '' && $v !== null) {
                            $specs[$k] = $v;
                        }
                    }
                } elseif (is_string($validated['specifications'])) {
                    $decoded = json_decode($validated['specifications'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $specs = $decoded;
                    }
                }
            }

            $product->name          = $validated['name'];
            $product->category_id   = (int)$validated['category_id'];
            $product->cost_price    = round((float)$validated['cost_price'], 2);
            $product->selling_price = round((float)$validated['selling_price'], 2);
            $product->description   = $validated['description'] ?? '';
            $product->specifications= $specs;

            // keep compatibility with either column
            if (array_key_exists('is_active', $validated) && \Schema::hasColumn('products', 'is_active')) {
                $product->is_active = (bool)$validated['is_active'];
            }
            if (array_key_exists('status', $validated) && \Schema::hasColumn('products', 'status')) {
                $product->status = $validated['status'];
            }

            $product->save();

            return redirect()->route('products.show', $product)
                ->with('success', 'Product updated successfully.');
        } catch (\Throwable $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }

    public function destroy(Product $product, StockService $stockApi)
    {
        // Don’t directly touch Stock or Order DBs; check availability via REST.
        try {
            $avail = collect($stockApi->availability((int)$product->id, null));
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Stock service unavailable; cannot validate deletion.']);
        }

        $total = (int)$avail->sum('available_quantity');

        if ($total > 0) {
            // Enforce business rule: ask user to clear/transfer stock via Orders/Stock first
            return back()->withErrors([
                'error' => "Cannot deactivate product while {$total} units exist in branches. " .
                           "Please transfer/receive/cancel to clear stock first."
            ]);
        }

        // Safe to deactivate locally
        DB::transaction(function () use ($product) {
            if (\Schema::hasColumn('products', 'is_active')) {
                $product->update(['is_active' => false]);
            } elseif (\Schema::hasColumn('products', 'status')) {
                $product->update(['status' => 'inactive']);
            }
        });

        return redirect()->route('products.index')
            ->with('success', 'Product deactivated successfully.');
    }

    // Add Category with auto-slug (local to Product module)
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:product_categories,name',
            'description'  => 'nullable|string',
            'factory_type' => 'required|string|in:smartphones-accessories,computers-peripherals,home-entertainment,wearables-smart-devices',
        ]);

        ProductCategory::create([
            'name'         => $validated['name'],
            'slug'         => Str::slug($validated['name']),
            'description'  => $validated['description'] ?? null,
            'status'       => 'active',
            'factory_type' => $validated['factory_type'],
        ]);

        return redirect()->route('products.index')
            ->with('success', 'Category and Factory mapping added successfully');
    }
}
