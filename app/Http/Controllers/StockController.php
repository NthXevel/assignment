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
        $this->middleware('permission:manage_stock')->except(['index', 'show', 'lowStock']);;

        // Attach low stock observer
        Stock::observe(LowStockObserver::class);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $branches = Branch::all();
        $products = Product::where('is_active', true)->get();
        $branchFilter = $request->branch;

        $query = Stock::with(['product', 'branch']);

        // Branch filter
        if ($branchFilter) {
            $query->where('branch_id', $branchFilter);
        }

        // Search filter
        if ($request->search) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        // Sorting
        switch ($request->sort) {
            case 'quantity_asc':
                $query->orderBy('quantity', 'asc');
                break;
            case 'quantity_desc':
                $query->orderBy('quantity', 'desc');
                break;
            default:
                $query->orderBy('id', 'asc');
        }

        $stocks = $query->paginate(20)->withQueryString();

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

    public function edit(Stock $stock)
    {
        return view('stocks.edit', compact('stock'));
    }

    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'minimum_threshold' => 'required|integer|min:0',
        ]);

        $oldQuantity = $stock->quantity;
        $stock->update($validated);

        // Log the quantity change
        if ($oldQuantity != $validated['quantity']) {
            StockMovement::create([
                'stock_id' => $stock->id,
                'quantity_change' => $validated['quantity'] - $oldQuantity,
                'reason' => 'Manual adjustment by ' . auth()->user()->username,
                'balance_after' => $validated['quantity'],
            ]);
        }

        return redirect()->route('stocks.show', $stock)
            ->with('success', 'Stock updated successfully');
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
            ->whereRaw('quantity <= minimum_threshold')
            ->paginate(20);

        return view('stocks.low-stock', compact('lowStocks'));
    }
}
