<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Observers\LowStockObserver;

class StockController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage_stock')->except(['index', 'show', 'lowStock']);

        // Attach low stock observer
        Stock::observe(LowStockObserver::class);
    }

    public function index(Request $request)
    {
        $branchFilter = $request->branch;
        $search = $request->search;
        $sort = $request->sort;

        $query = Stock::with(['product', 'branch'])
            //only active products
            ->whereHas('product', function ($q) {
                $q->where('is_active', 1); // only active products
            })
            //only active branches
            ->whereHas('branch', function ($q) {
                $q->where('status', 'active'); // change to is_active if that's your column
            });

        if ($branchFilter) {
            $query->where('branch_id', $branchFilter);
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('quantity', '<=', 'minimum_threshold');
        }

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->where('is_active', true);
            });
        }

        if ($sort === 'quantity_asc') {
            $query->orderBy('quantity', 'asc');
        } elseif ($sort === 'quantity_desc') {
            $query->orderBy('quantity', 'desc');
        } else {
            $query->latest();
        }

        $stocks = $query->paginate(10)->appends($request->query());
        $branches = Branch::where('status', 'active')->get();

        return view('stocks.index', compact('stocks', 'branches', 'branchFilter'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)->get();
        $branches = Branch::all();

        return view('stocks.create', compact('products', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|integer|min:0',
            'minimum_threshold' => 'required|integer|min:0',
        ]);

        // Check if stock record already exists
        $existingStock = Stock::where('product_id', $validated['product_id'])
            ->where('branch_id', $validated['branch_id'])
            ->first();

        if ($existingStock) {
            return back()->with('error', 'Stock record already exists for this product at this branch');
        }

        Stock::create($validated);

        return redirect()->route('stocks.index')
            ->with('success', 'Stock record created successfully');
    }

    public function show(Stock $stock)
    {
        $stock->load('product', 'branch');
        $movements = StockMovement::where('stock_id', $stock->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('stocks.show', compact('stock', 'movements'));
    }

    public function adjust(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'quantity_change' => 'required|integer',
            'reason' => 'required|string|max:255',
        ]);

        $stock->updateQuantity($validated['quantity_change'], $validated['reason']);

        return back()->with('success', 'Stock quantity adjusted successfully');
    }

    public function lowStock()
    {
        $lowStocks = Stock::with(['product', 'branch'])
            ->whereHas('product', function ($q) {
                $q->where('is_active', true);
            })
            ->whereRaw('quantity <= minimum_threshold')
            ->paginate(20);

        return view('stocks.low-stock', compact('lowStocks'));
    }

    /**
     * Update stock quantity with custom amount - preserves all filters
     */
    public function updateQuantity(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'new_quantity' => 'required|integer|min:0'
        ]);

        $oldQuantity = $stock->quantity;
        $stock->quantity = $validated['new_quantity'];
        $stock->save();

        // Log the stock movement
        if ($oldQuantity != $validated['new_quantity']) {
            StockMovement::create([
                'stock_id' => $stock->id,
                'quantity_change' => $validated['new_quantity'] - $oldQuantity,
                'reason' => 'Direct edit by ' . auth()->user()->username,
                'balance_after' => $validated['new_quantity'],
            ]);
        }

        // Preserve all filters when redirecting
        $queryParams = $request->only(['branch', 'search', 'sort', 'page']);

        return redirect()->route('stocks.index', $queryParams)
            ->with('success', "Stock updated: {$stock->product->name} quantity changed from {$oldQuantity} to {$validated['new_quantity']}");
    }

    /**
     * Adjust stock quantity by custom amount (+ or -)
     */
    public function adjustQuantity(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'action' => 'required|in:increase,decrease',
            'amount' => 'required|integer|min:1|max:1000'
        ]);

        $action = $validated['action'];
        $amount = $validated['amount'];
        $oldQuantity = $stock->quantity;

        if ($action === 'increase') {
            $stock->quantity += $amount;
            $actionText = "increased by {$amount}";
        } elseif ($action === 'decrease') {
            $stock->quantity = max(0, $stock->quantity - $amount);
            $actionText = "decreased by {$amount}";
        }

        $stock->save();

        // Log the stock movement
        StockMovement::create([
            'stock_id' => $stock->id,
            'quantity_change' => $stock->quantity - $oldQuantity,
            'reason' => ucfirst($action) . " by {$amount} - " . auth()->user()->username,
            'balance_after' => $stock->quantity,
        ]);

        // Preserve all filters when redirecting
        $queryParams = $request->only(['branch', 'search', 'sort', 'page']);

        return redirect()->route('stocks.index', $queryParams)
            ->with('success', "{$stock->product->name} stock {$actionText} (from {$oldQuantity} to {$stock->quantity})");
    }
}