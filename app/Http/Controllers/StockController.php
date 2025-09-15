<?php
// Author: Leong Kee Zheng
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Observers\LowStockObserver;
use App\Services\ProductService;
use App\Services\BranchService;

class StockController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage_stock')->except(['index', 'show', 'lowStock']);

        // Attach low stock observer (internal to Stock module)
        Stock::observe(LowStockObserver::class);
    }

    /**
     * List stock without joining Product/Branch tables.
     * Uses ProductService/BranchService for names and "active" filters.
     */
    public function index(Request $request, ProductService $productsApi, BranchService $branchesApi)
    {
        $branchFilter = $request->branch;
        $search = trim((string)$request->search);
        $sort = $request->sort;

        // Pull active branches & products via APIs (cached in services for speed)
        $activeBranches = collect($branchesApi->listActive());
        $activeBranchIds = $activeBranches->pluck('id')->map(fn($v) => (int)$v)->all();

        $activeProducts = collect($productsApi->listActive()); // [{id,name,category_name,selling_price}]
        $activeProductIds = $activeProducts->pluck('id')->map(fn($v) => (int)$v)->all();

        // Build a map for view display (id => product row), same for branches.
        $productsMap = $activeProducts->keyBy('id');
        $branches = $activeBranches->map(fn($b) => (object)$b); // for dropdowns

        // Start query on our own Stock table only
        $query = Stock::query()
            // Enforce "only active branches" and "only active products" without joins
            ->whereIn('branch_id', $activeBranchIds)
            ->whereIn('product_id', $activeProductIds);

        if ($branchFilter) {
            $query->where('branch_id', (int)$branchFilter);
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('quantity', '<=', 'minimum_threshold');
        }

        if ($search !== '') {
            // Filter products by name at the API layer (PHP), then constrain Stock by IDs
            $matchedProductIds = $activeProducts
                ->filter(fn($p) => stripos($p['name'] ?? '', $search) !== false)
                ->pluck('id')->map(fn($v) => (int)$v)->values()->all();

            // If nothing matches, short-circuit to empty result
            if (empty($matchedProductIds)) {
                $stocks = $query->whereRaw('1=0')->paginate(10)->appends($request->query());
                return view('stocks.index', [
                    'stocks'       => $stocks,
                    'branches'     => $branches,
                    'branchFilter' => $branchFilter,
                    'productsMap'  => $productsMap,
                ]);
            }

            $query->whereIn('product_id', $matchedProductIds);
        }

        if ($sort === 'quantity_asc') {
            $query->orderBy('quantity', 'asc');
        } elseif ($sort === 'quantity_desc') {
            $query->orderBy('quantity', 'desc');
        } else {
            // falls back to created_at desc if timestamps exist; else id desc
            $query->latest();
        }

        $stocks = $query->paginate(10)->appends($request->query());

        return view('stocks.index', [
            'stocks'       => $stocks,
            'branches'     => $branches,
            'branchFilter' => $branchFilter,
            'productsMap'  => $productsMap, // use in Blade to show product names
        ]);
    }

    /**
     * Create form: pull lists from APIs (no DB joins)
     */
    public function create(ProductService $productsApi, BranchService $branchesApi)
    {
        $products = collect($productsApi->listActive())->map(fn($p) => (object)$p);
        $branches = collect($branchesApi->listActive())->map(fn($b) => (object)$b);

        return view('stocks.create', compact('products', 'branches'));
    }

    /**
     * Create stock record (Stock module owns this table).
     * Cross-module entity checks are done via APIs, not DB "exists".
     */
    public function store(Request $request, ProductService $productsApi, BranchService $branchesApi)
    {
        $validated = $request->validate([
            'product_id'         => 'required|integer|min:1',
            'branch_id'          => 'required|integer|min:1',
            'quantity'           => 'required|integer|min:0',
            'minimum_threshold'  => 'required|integer|min:0',
        ]);

        // Validate cross-module entities via REST
        $productsApi->get((int)$validated['product_id']);
        $branchesApi->get((int)$validated['branch_id']);

        // Enforce uniqueness at (product_id, branch_id) to avoid duplicates
        $exists = Stock::where('product_id', (int)$validated['product_id'])
            ->where('branch_id', (int)$validated['branch_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Stock record already exists for this product at this branch');
        }

        Stock::create([
            'product_id'        => (int)$validated['product_id'],
            'branch_id'         => (int)$validated['branch_id'],
            'quantity'          => (int)$validated['quantity'],
            'minimum_threshold' => (int)$validated['minimum_threshold'],
        ]);

        return redirect()->route('stocks.index')
            ->with('success', 'Stock record created successfully');
    }

    /**
     * Show stock details (enrich product/branch via APIs).
     */
    public function show(Stock $stock, ProductService $productsApi, BranchService $branchesApi)
    {
        $product = $productsApi->get((int)$stock->product_id);
        $branch  = $branchesApi->get((int)$stock->branch_id);

        $movements = StockMovement::where('stock_id', $stock->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Pass $product/$branch arrays to the view instead of Eloquent relations
        return view('stocks.show', compact('stock', 'movements', 'product', 'branch'));
    }

    /**
     * Adjust quantity by delta using your existing domain method.
     * This stays inside the Stock module (no cross-module reads).
     */
    public function adjust(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'quantity_change' => 'required|integer',
            'reason'          => 'required|string|max:255',
        ]);

        $stock->updateQuantity($validated['quantity_change'], $validated['reason']);

        return back()->with('success', 'Stock quantity adjusted successfully');
    }

    /**
     * Low-stock page without joining Product/Branch.
     */
    public function lowStock(ProductService $productsApi)
    {
        $activeProducts = collect($productsApi->listActive());
        $activeProductIds = $activeProducts->pluck('id')->map(fn($v) => (int)$v)->all();
        $productsMap = $activeProducts->keyBy('id');

        $lowStocks = Stock::query()
            ->whereIn('product_id', $activeProductIds)
            ->whereColumn('quantity', '<=', 'minimum_threshold')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('stocks.low-stock', compact('lowStocks', 'productsMap'));
    }

    /**
     * Update stock to an absolute value; flash message enriched via ProductService.
     */
    public function updateQuantity(Request $request, Stock $stock, ProductService $productsApi)
    {
        $validated = $request->validate([
            'new_quantity' => 'required|integer|min:0'
        ]);

        $oldQuantity = (int)$stock->quantity;
        $stock->quantity = (int)$validated['new_quantity'];
        $stock->save();

        // Log movement inside Stock module
        if ($oldQuantity !== (int)$validated['new_quantity']) {
            StockMovement::create([
                'stock_id'        => $stock->id,
                'quantity_change' => (int)$validated['new_quantity'] - $oldQuantity,
                'reason'          => 'Direct edit by ' . (auth()->user()->username ?? 'user'),
                'balance_after'   => (int)$validated['new_quantity'],
            ]);
        }

        // Get product name via API for the toast message
        $product = $productsApi->get((int)$stock->product_id);
        $productName = $product['name'] ?? ('#'.$stock->product_id);

        // Preserve filters
        $queryParams = $request->only(['branch', 'search', 'sort', 'page']);

        return redirect()->route('stocks.index', $queryParams)
            ->with('success', "Stock updated: {$productName} quantity changed from {$oldQuantity} to {$validated['new_quantity']}");
    }

    /**
     * Adjust by +/- amount; again fetch product name via API for message.
     */
    public function adjustQuantity(Request $request, Stock $stock, ProductService $productsApi)
    {
        $validated = $request->validate([
            'action' => 'required|in:increase,decrease',
            'amount' => 'required|integer|min:1|max:1000'
        ]);

        $action = $validated['action'];
        $amount = (int)$validated['amount'];
        $oldQuantity = (int)$stock->quantity;

        if ($action === 'increase') {
            $stock->quantity = $oldQuantity + $amount;
            $actionText = "increased by {$amount}";
        } else {
            $stock->quantity = max(0, $oldQuantity - $amount);
            $actionText = "decreased by {$amount}";
        }

        $stock->save();

        // Log movement
        StockMovement::create([
            'stock_id'        => $stock->id,
            'quantity_change' => (int)$stock->quantity - $oldQuantity,
            'reason'          => ucfirst($action) . " by {$amount} - " . (auth()->user()->username ?? 'user'),
            'balance_after'   => (int)$stock->quantity,
        ]);

        // Product name via API for message
        $product = $productsApi->get((int)$stock->product_id);
        $productName = $product['name'] ?? ('#'.$stock->product_id);

        // Preserve filters
        $queryParams = $request->only(['branch', 'search', 'sort', 'page']);

        return redirect()->route('stocks.index', $queryParams)
            ->with('success', "{$productName} stock {$actionText} (from {$oldQuantity} to {$stock->quantity})");
    }
}
