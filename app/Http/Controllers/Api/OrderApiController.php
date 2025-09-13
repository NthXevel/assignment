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
}
