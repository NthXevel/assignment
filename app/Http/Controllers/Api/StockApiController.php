<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Stock;
use App\Services\ProductService;
use App\Services\BranchService;

class StockApiController extends Controller
{
    // GET /api/stock/availability?product_id=2&exclude_branch=3
    public function availability(Request $request)
    {
        $data = $request->validate([
            'product_id'     => ['required','integer','min:1'],
            'exclude_branch' => ['nullable','integer','min:1'],
        ]);

        
        $q = Stock::with('branch')
            ->where('product_id', (int)$data['product_id'])
            ->where('quantity', '>', 0);

        if (!empty($data['exclude_branch'])) {
            $q->where('branch_id', '!=', (int)$data['exclude_branch']);
        }

        $rows = $q->get()->map(fn($s) => [
            'branch_id'          => $s->branch_id,
            'branch_name'        => optional($s->branch)->name,
            'available_quantity' => (int)$s->quantity,
        ]);

        return response()->json($rows);
    }

    // POST /api/stock/reserve { order_id, branch_id, items:[{product_id,quantity}] }
    public function reserve(Request $request, BranchService $branches, ProductService $products)
    {
        $data = $request->validate([
            'order_id'             => ['required','integer','min:1'],
            'branch_id'            => ['required','integer','min:1'],
            'items'                => ['required','array','min:1'],
            'items.*.product_id'   => ['required','integer','min:1'],
            'items.*.quantity'     => ['required','integer','min:1'],
        ]);

        // Verify branch & product existence via APIs
        $branches->get((int)$data['branch_id']); // throws if not found
        foreach ($data['items'] as $it) {
            $products->get((int)$it['product_id']); // throws if not found
        }

        try {
            DB::transaction(function () use ($data) {
                foreach ($data['items'] as $it) {
                    $stock = Stock::where('branch_id', (int)$data['branch_id'])
                        ->where('product_id', (int)$it['product_id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$stock || $stock->quantity < (int)$it['quantity']) {
                        abort(422, 'Insufficient stock for product '.$it['product_id']);
                    }

                    $stock->decrement('quantity', (int)$it['quantity']);
                }
            });
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'reserved']);
    }

    // POST /api/stock/release { order_id, branch_id, items:[...] } (undo reserve)
    public function release(Request $request, BranchService $branches, ProductService $products)
    {
        $data = $request->validate([
            'order_id'             => ['required','integer','min:1'],
            'branch_id'            => ['required','integer','min:1'],
            'items'                => ['required','array','min:1'],
            'items.*.product_id'   => ['required','integer','min:1'],
            'items.*.quantity'     => ['required','integer','min:1'],
        ]);

        $branches->get((int)$data['branch_id']);
        foreach ($data['items'] as $it) { $products->get((int)$it['product_id']); }

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $it) {
                $stock = Stock::firstOrCreate(
                    ['branch_id' => (int)$data['branch_id'], 'product_id' => (int)$it['product_id']],
                    ['quantity' => 0]
                );
                $stock->increment('quantity', (int)$it['quantity']);
            }
        });

        return response()->json(['status' => 'released']);
    }

    // POST /api/stock/receive { order_id, branch_id, items:[...] } (increment at receiver)
    public function receive(Request $request, BranchService $branches, ProductService $products)
    {
        $data = $request->validate([
            'order_id'             => ['required','integer','min:1'],
            'branch_id'            => ['required','integer','min:1'],
            'items'                => ['required','array','min:1'],
            'items.*.product_id'   => ['required','integer','min:1'],
            'items.*.quantity'     => ['required','integer','min:1'],
        ]);

        $branches->get((int)$data['branch_id']);
        foreach ($data['items'] as $it) { $products->get((int)$it['product_id']); }

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $it) {
                $stock = Stock::firstOrCreate(
                    ['branch_id' => (int)$data['branch_id'], 'product_id' => (int)$it['product_id']],
                    ['quantity' => 0]
                );
                $stock->increment('quantity', (int)$it['quantity']);
            }
        });

        return response()->json(['status' => 'received']);
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'product_id'        => 'required|integer|min:1',
            'branch_id'         => 'required|integer|min:1',
            'quantity'          => 'nullable|integer|min:0',
            'minimum_threshold' => 'nullable|integer|min:0',
            'preserve_existing' => 'nullable|boolean', // default: true
        ]);

        $qty       = array_key_exists('quantity', $data) ? (int)$data['quantity'] : null;
        $min       = array_key_exists('minimum_threshold', $data) ? (int)$data['minimum_threshold'] : null;
        $preserve  = array_key_exists('preserve_existing', $data) ? (bool)$data['preserve_existing'] : true;

        $stock = \App\Models\Stock::firstOrNew([
            'product_id' => (int)$data['product_id'],
            'branch_id'  => (int)$data['branch_id'],
        ]);

        if ($stock->exists && $preserve) {
            // Keep existing quantity; optionally update threshold
            if ($min !== null) $stock->minimum_threshold = $min;
        } else {
            // Create or overwrite (when preserve=false)
            if ($qty !== null) $stock->quantity = $qty;
            if ($min !== null) $stock->minimum_threshold = $min;
        }

        // Defaults for brand new rows
        if (!$stock->exists && $qty === null) $stock->quantity = 0;
        if (!$stock->exists && $min === null) $stock->minimum_threshold = 0;

        $stock->save();

        return response()->json([
            'id'                 => $stock->id,
            'product_id'         => $stock->product_id,
            'branch_id'          => $stock->branch_id,
            'quantity'           => $stock->quantity,
            'minimum_threshold'  => $stock->minimum_threshold,
        ], 200);
    }
}
