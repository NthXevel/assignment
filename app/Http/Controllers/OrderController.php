<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use App\Strategies\Orders\OrderContext;
use App\Strategies\Orders\StandardOrderStrategy;
use App\Strategies\Orders\UrgentOrderStrategy;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * List orders with filters
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Order::with(['requestingBranch', 'supplyingBranch', 'creator'])
            // Branch manager restriction
            ->when($user->role !== 'admin', function ($q) use ($user) {
                $q->where(function ($subQ) use ($user) {
                    $subQ->where('requesting_branch_id', $user->branch_id)
                         ->orWhere('supplying_branch_id', $user->branch_id);
                });
            });

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by branch (requesting OR supplying)
        if ($request->filled('branch')) {
            $query->where(function ($q) use ($request) {
                $q->where('requesting_branch_id', $request->branch)
                  ->orWhere('supplying_branch_id', $request->branch);
            });
        }

        // Filter by search (order number OR creator username)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('creator', function ($sub) use ($search) {
                      $sub->where('username', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        // Show branches only if admin (for filter dropdown)
        $branches = $user->role === 'admin' ? Branch::orderBy('name')->get() : collect();

        return view('orders.index', compact('orders', 'branches'));
    }

    /**
     * Show create order form
     */
    public function create()
    {
        $branches = Branch::where('id', '!=', auth()->user()->branch_id)->get();
        $products = Product::with('category')->where('is_active', true)->get();
        $mainBranch = Branch::getMainBranch();

        return view('orders.create', compact('branches', 'products', 'mainBranch'));
    }

    /**
     * Store new order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplying_branch_id' => 'required|exists:branches,id',
            'priority' => 'required|in:standard,urgent',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Create order
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()), // Automatically encrypted
            'requesting_branch_id' => auth()->user()->branch_id,
            'supplying_branch_id' => $validated['supplying_branch_id'],
            'created_by' => auth()->id(),
            'priority' => $validated['priority'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $product->cost_price,
                'total_price' => $item['quantity'] * $product->cost_price,
            ]);
        }

        // Strategy pattern
        $context = new OrderContext();
        $strategy = $validated['priority'] === 'urgent'
            ? new UrgentOrderStrategy()
            : new StandardOrderStrategy();

        $context->setStrategy($strategy);
        $context->processOrder($order);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order created successfully (Priority: ' . $context->getPriority() . ', Shipping: ' . $context->getShippingTime($order) . ' days)');
    }

    /**
     * Show single order details
     */
    public function show(Order $order)
    {
        $order->load(['requestingBranch', 'supplyingBranch', 'creator', 'items.product']);

        if (auth()->user()->role !== 'admin' &&
            !in_array(auth()->user()->branch_id, [$order->requesting_branch_id, $order->supplying_branch_id])) {
            abort(403);
        }

        return view('orders.show', compact('order'));
    }

    /**
     * Approve an order
     */
    public function approve(Order $order)
    {
        foreach ($order->items as $item) {
            $stock = Stock::where('product_id', $item->product_id)
                ->where('branch_id', $order->supplying_branch_id)
                ->first();

            if (!$stock || $stock->quantity < $item->quantity) {
                return back()->with('error', "Insufficient stock for {$item->product->name}");
            }
        }

        $order->approve();

        return back()->with('success', 'Order approved successfully');
    }

    /**
     * Ship an order
     */
    public function ship(Order $order)
    {
        if ($order->status !== 'approved') {
            return back()->with('error', 'Order must be approved before shipping');
        }

        $order->ship();

        return back()->with('success', 'Order shipped successfully');
    }

    /**
     * Receive an order
     */
    public function receive(Order $order)
    {
        if ($order->status !== 'shipped') {
            return back()->with('error', 'Order must be shipped before receiving');
        }

        if (auth()->user()->branch_id !== $order->requesting_branch_id) {
            abort(403, 'Only requesting branch can receive orders');
        }

        $order->receive();

        return back()->with('success', 'Order received successfully');
    }

    /**
     * Cancel an order
     */
    public function cancel(Order $order)
    {
        if (!in_array($order->status, ['pending', 'approved'])) {
            return back()->with('error', 'Cannot cancel shipped or received orders');
        }

        $order->status = 'cancelled';
        $order->save();

        return back()->with('success', 'Order cancelled successfully');
    }
}
