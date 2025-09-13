<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use Illuminate\Http\Request;
use App\Models\Stock;

class StockApiController extends Controller
{
    public function __construct(private StockService $service)
    {
        $this->middleware(['auth:sanctum']);
    }

    public function index(Request $request)
    {
        $stocks = $this->service->list([
            'branch_id' => $request->get('branch_id'),
            'low_stock' => $request->boolean('low_stock'),
            'search' => $request->get('search'),
            'per_page' => $request->get('per_page'),
        ]);
        return response()->json($stocks);
    }

    public function show(Stock $stock)
    {
        return response()->json($stock->load(['product', 'branch']));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|integer|min:0',
            'minimum_threshold' => 'required|integer|min:0',
        ]);
        $stock = $this->service->create($validated);
        return response()->json($stock, 201);
    }

    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'quantity' => 'nullable|integer|min:0',
            'minimum_threshold' => 'nullable|integer|min:0',
        ]);
        $updated = $this->service->update($stock, $validated);
        return response()->json($updated);
    }

    public function destroy(Stock $stock)
    {
        $this->service->delete($stock);
        return response()->json(['message' => 'Stock deleted']);
    }

    public function adjust(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'action' => 'required|in:increase,decrease',
            'amount' => 'required|integer|min:1|max:1000'
        ]);
        $updated = $this->service->adjust($stock, $validated['amount'], $validated['action'], auth()->user()->username ?? 'api');
        return response()->json($updated);
    }

    public function updateQuantity(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'new_quantity' => 'required|integer|min:0'
        ]);
        $updated = $this->service->updateQuantity($stock, $validated['new_quantity'], auth()->user()->username ?? 'api');
        return response()->json($updated);
    }
}



