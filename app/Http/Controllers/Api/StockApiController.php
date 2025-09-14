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
        //$branches->get((int)$data['branch_id']); // throws if not found
        //foreach ($data['items'] as $it) {
        //    $products->get((int)$it['product_id']); // throws if not found
        //}

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

        //$branches->get((int)$data['branch_id']);
        //foreach ($data['items'] as $it) { $products->get((int)$it['product_id']); }

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

    public function list(Request $r)
    {
        $q = \App\Models\Stock::query()->with(['product:id,name,selling_price','branch:id,name']);

        if ($r->filled('branch_id')) $q->where('branch_id', (int)$r->get('branch_id'));
        if ($r->boolean('low_stock')) $q->whereColumn('quantity', '<=', 'minimum_threshold');

        if ($r->filled('sort')) {
            if ($r->get('sort') === 'quantity_asc') $q->orderBy('quantity');
            elseif ($r->get('sort') === 'quantity_desc') $q->orderByDesc('quantity');
            else $q->latest();
        } else {
            $q->latest();
        }

        $per  = max(1, (int)$r->get('per_page', 10));
        $page = max(1, (int)$r->get('page', 1));
        $p    = $q->paginate($per, ['*'], 'page', $page);

        $items = $p->getCollection()->map(fn($s) => [
            'product_id'         => $s->product_id,
            'branch_id'          => $s->branch_id,
            'quantity'           => (int)$s->quantity,
            'minimum_threshold'  => (int)$s->minimum_threshold,
            'product'            => ['id'=>$s->product->id, 'name'=>$s->product->name, 'selling_price'=>(float)$s->product->selling_price],
            'branch'             => ['id'=>$s->branch->id,  'name'=>$s->branch->name],
        ])->values();

        return response()->json([
            'data'         => $items,
            'total'        => $p->total(),
            'per_page'     => $p->perPage(),
            'current_page' => $p->currentPage(),
        ]);
    }

    public function movements(Request $r)
    {
        $q = \App\Models\StockMovement::with(['stock.product:id,name', 'stock.branch:id,name'])->latest();

        if ($r->filled('reason')) $q->where('reason', 'like', '%'.$r->get('reason').'%');
        if ($r->filled('search')) $q->whereHas('stock.product', fn($qq) => $qq->where('name', 'like', '%'.$r->get('search').'%'));
        if ($r->filled('branch_id')) $q->whereHas('stock', fn($qq) => $qq->where('branch_id', (int)$r->get('branch_id')));

        $per  = max(1, (int)$r->get('per_page', 10));
        $page = max(1, (int)$r->get('page', 1));
        $p    = $q->paginate($per, ['*'], 'page', $page);

        $items = $p->getCollection()->map(fn($m) => [
            'id'              => $m->id,
            'stock_id'        => $m->stock_id,
            'quantity_change' => (int)$m->quantity_change,
            'balance_after'   => (int)$m->balance_after,
            'reason'          => $m->reason,
            'created_at'      => optional($m->created_at)->toISOString(),
            'product'         => ['id'=>$m->stock->product->id, 'name'=>$m->stock->product->name],
            'branch'          => ['id'=>$m->stock->branch->id,  'name'=>$m->stock->branch->name],
        ])->values();

        return response()->json([
            'data'         => $items,
            'total'        => $p->total(),
            'per_page'     => $p->perPage(),
            'current_page' => $p->currentPage(),
        ]);
    }

    public function value(Request $r)
    {
        $branchId = (int)$r->get('branch_id');
        if ($branchId <= 0) return response()->json(['error'=>'branch_id required'], 422);

        $value = \App\Models\Stock::where('branch_id', $branchId)
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->sum(\DB::raw('stocks.quantity * products.selling_price'));

        return response()->json(['value' => (float)$value]);
    }
}
