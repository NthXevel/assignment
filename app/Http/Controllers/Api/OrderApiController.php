<?php

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
            } catch (\Illuminate\Http\Client\RequestException $e) {
                return response()->json([
                    'error' => $e->response?->json('error') ?? 'Reserve failed'
                ], 422);
            }

            // ship (status only)
            $order->status = 'shipped';
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
            $order->save();
        }

        return response()->json([
            'order_id' => $order->id,
            'status'   => $order->status,
        ], 201);
    }
}
