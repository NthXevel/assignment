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
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // List orders
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Order::with(['requestingBranch', 'supplyingBranch', 'creator'])
            ->when($user->role !== 'admin', function ($q) use ($user) {
                $q->where('requesting_branch_id', $user->branch_id)
                    ->orWhere('supplying_branch_id', $user->branch_id);
            });

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->filled('branch')) {
            $query->where(function ($q) use ($request) {
                $q->where('requesting_branch_id', $request->branch)
                    ->orWhere('supplying_branch_id', $request->branch);
            });
        }

        // Search by order number, creator, requesting branch, supplying branch
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('creator', fn($sub) => $sub->where('username', 'like', "%{$search}%"))
                    ->orWhereHas('requestingBranch', fn($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('supplyingBranch', fn($sub) => $sub->where('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $branches = $user->role === 'admin' ? Branch::orderBy('name')->get() : collect();

        return view('orders.index', compact('orders', 'branches'));
    }

    // Show create order form
    public function create()
    {
        $products = Product::with(['category', 'stocks.branch'])->get();
        $branches = Branch::where('status', 'active')->get();
        
        return view('orders.create', compact('products', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'supplying_branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Create Order
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'requesting_branch_id' => auth()->user()->branch_id,
            'supplying_branch_id' => $request->supplying_branch_id,
            'created_by' => auth()->id(),
            'status' => 'pending',
            'priority' => 'normal',
            'total_amount' => $product->price * $request->quantity,
            'notes' => $request->notes,
        ]);

        // Add Order Item (single product)
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'price' => $product->price * $request->quantity,
        ]);

        return redirect()->route('orders.show', $order->id)
            ->with('success', 'Order created successfully!');
    }

    // Show single order
    public function show(Order $order)
    {
        // Load related models
        $order->load(['requestingBranch', 'supplyingBranch', 'creator', 'items.product']);

        $user = auth()->user();

        // Only admin or branches involved in the order can view
        if ($user->role !== 'admin' && !in_array($user->branch_id, [$order->requesting_branch_id, $order->supplying_branch_id])) {
            abort(403, 'Unauthorized access to this order.');
        }

        return view('orders.show', compact('order'));
    }


    // Approve order
    public function approve(Order $order)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Only pending orders can be approved');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('branch_id', $order->supplying_branch_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->quantity < $item->quantity) {
                    throw new \Exception("Insufficient stock for {$item->product->name}");
                }

                $stock->decrement('quantity', $item->quantity);
            }

            $order->status = 'approved';
            $order->save();
        });

        return back()->with('success', 'Order approved!');
    }

    // Ship order
    public function ship(Order $order)
    {
        if ($order->status !== 'approved') {
            return back()->with('error', 'Order must be approved before shipping');
        }

        $order->status = 'shipped';
        $order->save();

        return back()->with('success', 'Order shipped!');
    }

    // Receive order
    public function receive(Order $order)
    {
        if ($order->status !== 'shipped') {
            return back()->with('error', 'Order must be shipped before receiving');
        }

        if (auth()->user()->branch_id !== $order->requesting_branch_id) {
            abort(403);
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $stock = Stock::firstOrCreate(
                    ['product_id' => $item->product_id, 'branch_id' => $order->requesting_branch_id],
                    ['quantity' => 0]
                );
                $stock->increment('quantity', $item->quantity);
            }

            $order->status = 'received';
            $order->save();
        });

        return back()->with('success', 'Order received!');
    }

    // Cancel order
    public function cancel(Order $order)
    {
        if (!in_array($order->status, ['pending', 'approved'])) {
            return back()->with('error', 'Cannot cancel shipped or received orders');
        }

        DB::transaction(function () use ($order) {
            if ($order->status === 'approved') {
                foreach ($order->items as $item) {
                    $stock = Stock::where('product_id', $item->product_id)
                        ->where('branch_id', $order->supplying_branch_id)
                        ->lockForUpdate()
                        ->first();
                    if ($stock)
                        $stock->increment('quantity', $item->quantity);
                }
            }
            $order->status = 'cancelled';
            $order->save();
        });

        return back()->with('success', 'Order cancelled!');
    }

    // Fetch branches with stock for a given product
    public function getBranchesWithStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $branches = Stock::with('branch')
            ->where('product_id', $request->product_id)
            ->where('quantity', '>', 0)
            ->get()
            ->map(function ($stock) {
                return [
                    'branch_id' => $stock->branch_id,
                    'branch_name' => $stock->branch->name,
                    'available_quantity' => $stock->quantity,
                ];
            });

        return response()->json($branches);
    }
}
