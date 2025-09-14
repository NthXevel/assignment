<?php
// Author: Lee Kai Yi
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderItem;
use App\Strategies\Orders\OrderContext;
use App\Strategies\Orders\StandardOrderStrategy;
use App\Strategies\Orders\UrgentOrderStrategy;
use App\Services\StockService;

class OrderApiController extends Controller
{
    // POST /api/orders
    public function store(Request $r)
    {
        $v = $r->validate([
            'requesting_branch_id' => ['required','integer','different:supplying_branch_id','exists:branches,id'],
            'supplying_branch_id'  => ['required','integer','exists:branches,id'],
            'priority'             => ['required','in:standard,urgent'],
            'notes'                => ['nullable','string','max:500'],
            'items'                => ['required','array','min:1'],
            'items.*.product_id'   => ['required','integer','exists:products,id'],
            'items.*.quantity'     => ['required','integer','min:1'],
        ]);

        $uid = Auth::id();

        $order = DB::transaction(function () use ($v, $uid) {
            $o = new Order();
            $o->requesting_branch_id = $v['requesting_branch_id'];
            $o->supplying_branch_id  = $v['supplying_branch_id'];
            $o->priority             = $v['priority'];
            $o->notes                = $v['notes'] ?? null;
            $o->created_by           = $uid;
            $o->order_number         = Str::upper(Str::random(10));
            $o->status               = 'pending';
            $o->save();

            foreach ($v['items'] as $it) {
                OrderItem::create([
                    'order_id'   => $o->id,
                    'product_id' => $it['product_id'],
                    'quantity'   => $it['quantity'],
                ]);
            }

            $ctx = new OrderContext();
            $ctx->setStrategy($v['priority'] === 'urgent'
                ? new UrgentOrderStrategy() : new StandardOrderStrategy());
            $ctx->processOrder($o);

            return $o->fresh(['items']);
        });

        return response()->json([
            'status' => 'created',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ], 201);
    }

    /**
     * POST /api/orders/return
     * Body: {
     *   requesting_branch_id: int,   // destination (main)
     *   supplying_branch_id: int,    // source (closing branch)
     *   items: [{product_id:int, quantity:int, unit_price:float}],
     *   notes?: string,
     *   created_by?: int,
     *   auto_complete?: bool  // if true: reserve->ship->receive in one call
     * }
     */
    public function createReturn(Request $request, StockService $stockApi)
    {
        $data = $request->validate([
            'requesting_branch_id' => 'required|integer|min:1',
            'supplying_branch_id'  => 'required|integer|min:1|different:requesting_branch_id',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer|min:1',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string|max:1000',
            'created_by'           => 'nullable|integer|min:1',
            'auto_complete'        => 'nullable|boolean',
        ]);

        $auto = (bool)($data['auto_complete'] ?? true);

        $order = DB::transaction(function () use ($data) {
            $order = Order::create([
                'order_number'         => 'RET-'.strtoupper(Str::random(10)),
                'requesting_branch_id' => (int)$data['requesting_branch_id'],
                'supplying_branch_id'  => (int)$data['supplying_branch_id'],
                'created_by'           => $data['created_by'] ?? auth()->id(),
                'status'               => 'pending',
                'priority'             => 'urgent',
                'notes'                => $data['notes'] ?? 'Auto return (product deactivate)',
            ]);

            foreach ($data['items'] as $it) {
                $order->items()->create([
                    'product_id' => (int)$it['product_id'],
                    'quantity'   => (int)$it['quantity'],
                    'unit_price' => (float)($it['unit_price'] ?? 0),
                    'total_price'=> (float)($it['unit_price'] ?? 0) * (int)$it['quantity'],
                ]);
            }

            return $order->fresh(['items']);
        });

        if ($auto) {
            // Reserve at supplier (decrement) -> ship -> receive at requester (increment)
            $items = $order->items->map(fn($i) => [
                'product_id' => $i->product_id,
                'quantity'   => (int)$i->quantity,
            ])->all();

            // reserve
            //$stockApi->reserve($order->id, $order->supplying_branch_id, $items);
            try {
                $stockApi->reserve($order->id, $order->supplying_branch_id, $items);
                $order->status       = 'approved';
                $order->approved_at  = now();
                $order->save();
            } catch (\Illuminate\Http\Client\RequestException $e) {
                return response()->json([
                    'error' => $e->response?->json('error') ?? 'Reserve failed'
                ], 422);
            }

            // ship
            $order->status = 'shipped';
            $order->shipped_at  = now();
            $order->save();

            // receive
            //$stockApi->receive($order->id, $order->requesting_branch_id, $items);
            try {
                $stockApi->receive($order->id, $order->requesting_branch_id, $items);
            } catch (\Illuminate\Http\Client\RequestException $e) {
                return response()->json([
                    'error' => $e->response?->json('error') ?? 'Receive failed'
                ], 422);
            }

            $order->status = 'received';
            $order->received_at  = now();
            $order->save();
        }

        return response()->json([
            'order_id' => $order->id,
            'status'   => $order->status,
        ], 201);
    }

    // GET /api/orders
    public function index(Request $r)
    {
        $q = Order::query()->withCount('items');

        // Filters
        if ($r->filled('statuses')) {
            $statuses = array_filter(array_map('trim', explode(',', $r->get('statuses'))));
            if ($statuses) $q->whereIn('status', $statuses);
        } elseif ($r->filled('status')) {
            $q->where('status', $r->get('status'));
        }

        if ($r->filled('branch_id')) {
            $bid = (int)$r->get('branch_id');
            $q->where(function ($qq) use ($bid) {
                $qq->where('requesting_branch_id', $bid)
                ->orWhere('supplying_branch_id', $bid);
            });
        }

        if ($s = $r->get('search')) {
            $q->where('order_number', 'like', "%{$s}%");
        }

        if ($r->filled('date_from')) $q->whereDate('created_at', '>=', $r->get('date_from'));
        if ($r->filled('date_to'))   $q->whereDate('created_at', '<=', $r->get('date_to'));

        $per  = max(1, (int)$r->get('per_page', 10));
        $page = max(1, (int)$r->get('page', 1));
        $p    = $q->orderByDesc('created_at')->paginate($per, ['*'], 'page', $page);

        $items = $p->getCollection()->map(fn($o) => [
            'id'                    => $o->id,
            'order_number'          => $o->order_number,
            'status'                => $o->status,
            'priority'              => $o->priority,
            'requesting_branch_id'  => $o->requesting_branch_id,
            'supplying_branch_id'   => $o->supplying_branch_id,
            'created_by'            => $o->created_by,
            'items_count'           => $o->items_count,
            'created_at'            => optional($o->created_at)->toISOString(),
        ])->values();

        return response()->json([
            'data'         => $items,
            'total'        => $p->total(),
            'per_page'     => $p->perPage(),
            'current_page' => $p->currentPage(),
        ]);
    }

    // GET /api/orders/{id}
    public function show(int $id)
    {
        $o = Order::with(['items', 'requestingBranch:id,name', 'supplyingBranch:id,name'])->find($id);
        if (!$o) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'id'                   => $o->id,
            'order_number'         => $o->order_number,
            'status'               => $o->status,
            'priority'             => $o->priority,
            'requesting_branch_id' => $o->requesting_branch_id,
            'supplying_branch_id'  => $o->supplying_branch_id,
            'created_by'           => $o->created_by,
            'notes'                => $o->notes,
            'items' => $o->items->map(fn($i) => [
                'product_id'  => $i->product_id,
                'quantity'    => (int)$i->quantity,
                'unit_price'  => (float)$i->unit_price,
                'total_price' => (float)$i->total_price,
            ])->values(),
            'created_at' => optional($o->created_at)->toISOString(),
        ]);
    }

    // POST /api/orders/{id}/approve
    public function approve(int $id, StockService $stockApi)
    {
        $order = Order::with('items')->find($id);
        if (!$order) return response()->json(['error'=>'Not found'], 404);
        if ($order->status !== 'pending') {
            return response()->json(['error'=>'Only pending orders can be approved'], 422);
        }

        $items = $order->items->map(fn($i) => [
            'product_id' => $i->product_id,
            'quantity'   => (int)$i->quantity,
        ])->values()->all();

        try {
            $stockApi->reserve($order->id, $order->supplying_branch_id, $items);
            $order->status = 'approved';
            $order->approved_at  = now();
            $order->save();
            return response()->json(['ok'=>true, 'status'=>$order->status]);
        } catch (\Throwable $e) {
            return response()->json(['error'=>$e->getMessage()], 422);
        }
    }

    // POST /api/orders/{id}/ship
    public function ship(int $id)
    {
        $order = Order::find($id);
        if (!$order) return response()->json(['error'=>'Not found'], 404);
        if ($order->status !== 'approved') {
            return response()->json(['error'=>'Order must be approved before shipping'], 422);
        }
        $order->status = 'shipped';
        $order->shipped_at  = now();
        $order->save();
        return response()->json(['ok'=>true, 'status'=>$order->status]);
    }

    // POST /api/orders/{id}/receive
    public function receive(int $id, StockService $stockApi)
    {
        $order = Order::with('items')->find($id);
        if (!$order) return response()->json(['error'=>'Not found'], 404);
        if ($order->status !== 'shipped') {
            return response()->json(['error'=>'Order must be shipped before receiving'], 422);
        }

        $items = $order->items->map(fn($i) => [
            'product_id' => $i->product_id,
            'quantity'   => (int)$i->quantity,
        ])->values()->all();

        try {
            $stockApi->receive($order->id, $order->requesting_branch_id, $items);
            $order->status = 'received';
            $order->received_at  = now();
            $order->save();
            return response()->json(['ok'=>true, 'status'=>$order->status]);
        } catch (\Throwable $e) {
            return response()->json(['error'=>'Failed to receive: '.$e->getMessage()], 422);
        }
    }

    // POST /api/orders/{id}/cancel
    public function cancel(int $id, StockService $stockApi)
    {
        $order = Order::with('items')->find($id);
        if (!$order) return response()->json(['error'=>'Not found'], 404);
        if (!in_array($order->status, ['pending','approved'], true)) {
            return response()->json(['error'=>'Cannot cancel shipped or received orders'], 422);
        }

        try {
            DB::transaction(function () use ($order, $stockApi) {
                if ($order->status === 'approved') {
                    $items = $order->items->map(fn($i) => [
                        'product_id' => $i->product_id,
                        'quantity'   => (int)$i->quantity,
                    ])->values()->all();
                    $stockApi->release($order->id, $order->supplying_branch_id, $items);
                }
                $order->status = 'cancelled';
                $order->save();
            });
            return response()->json(['ok'=>true, 'status'=>$order->status]);
        } catch (\Throwable $e) {
            return response()->json(['error'=>'Failed to cancel: '.$e->getMessage()], 422);
        }
    }
}
