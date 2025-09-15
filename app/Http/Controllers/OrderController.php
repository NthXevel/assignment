<?php
// Author: Lee Kai Yi
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderItem;
use App\Strategies\Orders\OrderContext;
use App\Strategies\Orders\StandardOrderStrategy;
use App\Strategies\Orders\UrgentOrderStrategy;
use App\Services\StockService;
use App\Services\ProductService;
use App\Services\BranchService;
use App\Services\UserService;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // List orders - Updated to show latest first
    public function index(Request $request, BranchService $branchesApi, UserService $usersApi)
    {
        $user = auth()->user();

        $query = Order::orderByDesc('created_at')
            ->when($user->role !== 'admin', fn($q) =>
                $q->where(fn($qq)=>$qq
                    ->where('requesting_branch_id',$user->branch_id)
                    ->orWhere('supplying_branch_id',$user->branch_id)));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch')) {
            $query->where(function ($q) use ($request) {
                $q->where('requesting_branch_id', $request->branch)
                  ->orWhere('supplying_branch_id', $request->branch);
            });
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            if ($term !== '') {
                $query->where(function ($q) use ($term, $branchesApi) {
                    $matched = false;

                    // branch name -> ids via Branch API
                    try {
                        $branchIds = collect($branchesApi->listActive())
                            ->filter(fn($b) => stripos($b['name'] ?? '', $term) !== false)
                            ->pluck('id')->map(fn($v)=>(int)$v)->all();

                        if ($branchIds) {
                            $q->orWhereIn('requesting_branch_id', $branchIds)
                            ->orWhereIn('supplying_branch_id', $branchIds);
                        }
                    } catch (\Throwable $e) {
                        
                    }

                    // If nothing matched, force empty result
                    if (!$matched) {
                        $q->whereRaw('0 = 1'); // ensures no records returned
                    }
                });
            }
        }

        $orders = $query->paginate(10)->withQueryString();

        // resolve branch names via BranchService
        $ids = $orders->pluck('requesting_branch_id')
                    ->merge($orders->pluck('supplying_branch_id'))
                    ->unique()->values()->all();
                    
        $branchNameMap = collect($branchesApi->listActive())
            ->pluck('name','id'); // [id => name]

        return view('orders.index', compact('orders', 'branchNameMap'));
    }

    // Show create order form (products & branches from APIs)
    public function create(ProductService $productsApi, BranchService $branchesApi)
    {
        try {
            $products = collect($productsApi->listActive())->map(fn($p) => (object)$p);
        } catch (\Throwable $e) {
            $products = collect();
        }

        try {
            $branches = collect($branchesApi->listActive())->map(fn($b) => (object)$b);
        } catch (\Throwable $e) {
            $branches = collect();
        }

        return view('orders.create', compact('products', 'branches'));
    }

    public function store(Request $request, ProductService $productsApi, StockService $stockApi)
    {
        // Validate format; existence is checked
        $request->validate([
            'items.0.product_id'   => 'required|integer',
            'items.0.quantity'     => 'required|integer|min:1',
            'supplying_branch_id'  => 'required|integer',
            'priority'             => 'required|in:standard,urgent',
            'notes'                => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();

        if (!$user->branch_id) {
            return back()->withErrors(['error' => 'User must be assigned to a branch to create orders.']);
        }

        if ((int)$request->supplying_branch_id === (int)$user->branch_id) {
            return back()->withErrors(['supplying_branch_id' => 'Cannot request from your own branch.']);
        }

        $productId = (int) $request->input('items.0.product_id');
        $quantity  = (int) $request->input('items.0.quantity');

        // Fetch product via Product API (price/existence)
        try {
            $product = $productsApi->get($productId);
        } catch (\Throwable $e) {
            return back()->withErrors(['items.0.product_id' => 'Invalid product selected'])->withInput();
        }

        // Check stock availability via Stock API
        try {
            $availability = collect($stockApi->availability($productId, $user->branch_id));
        } catch (\Throwable $e) {
            return back()->withErrors(['stock' => 'Stock service unavailable'])->withInput();
        }

        $supplier = $availability->firstWhere('branch_id', (int)$request->supplying_branch_id);
        if (!$supplier || (int)$supplier['available_quantity'] < $quantity) {
            return back()->withErrors(['items.0.quantity' => 'Insufficient stock at selected branch'])->withInput();
        }

        // Create order + item
        $order = DB::transaction(function () use ($request, $product, $productId, $quantity, $user) {
            $order = Order::create([
                'order_number'         => 'ORD-'.strtoupper(Str::random(10)),
                'requesting_branch_id' => $user->branch_id,
                'supplying_branch_id'  => (int)$request->supplying_branch_id,
                'created_by'           => $user->id,
                'status'               => 'pending',
                'priority'             => $request->priority,
                'notes'                => $request->notes,
            ]);

            $unitPrice = (float) ($product['selling_price'] ?? 0);
            $order->items()->create([
                'product_id' => $productId,
                'quantity'   => $quantity,
                'unit_price' => $unitPrice,
                'total_price'=> $unitPrice * $quantity,
            ]);

            $ctx = new OrderContext();
            $ctx->setStrategy($request->priority === 'urgent'
                ? new UrgentOrderStrategy()
                : new StandardOrderStrategy());
            $ctx->processOrder($order);

            return $order;
        });

        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully!');
    }

    // Show single order
    public function show(Order $order)
    {
        $order->load(['requestingBranch', 'supplyingBranch', 'creator', 'items.product']);
        $user = auth()->user();

        if ($user->role !== 'admin' && !in_array($user->branch_id, [$order->requesting_branch_id, $order->supplying_branch_id])) {
            abort(403, 'Unauthorized access to this order.');
        }

        return view('orders.show', compact('order'));
    }

    // Approve order -> reserve stock at supplying branch via Stock API
    public function approve(Order $order, StockService $stockApi)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Only pending orders can be approved');
        }

        $user = auth()->user();
        if ($user->role !== 'admin' && $user->branch_id !== $order->supplying_branch_id) {
            abort(403, 'Only the supplying branch can approve this order.');
        }

        try {
            $items = $order->items->map(fn($i) => [
                'product_id' => $i->product_id,
                'quantity'   => (int)$i->quantity,
            ])->values()->all();

            $stockApi->reserve($order->id, $order->supplying_branch_id, $items);

            $order->fill([
                'status'      => 'approved',
                'approved_at' => now(),
            ])->save();

            return back()->with('success', 'Order approved and stock reserved!');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // Ship order
    public function ship(Order $order)
    {
        if ($order->status !== 'approved') {
            return back()->with('error', 'Order must be approved before shipping');
        }

        $user = auth()->user();
        if ($user->role !== 'admin' && $user->branch_id !== $order->supplying_branch_id) {
            abort(403, 'Only the supplying branch can ship this order.');
        }

        // Strategy-based shipping eligibility check
        $context = new OrderContext();
        if ($order->priority === 'urgent') {
            $context->setStrategy(new UrgentOrderStrategy());
        } else {
            $context->setStrategy(new StandardOrderStrategy());
        }

        if (!$context->canShip($order)) {
            return back()->with('error', "Send urgent's order first");
        }

        $order->fill([
            'status'     => 'shipped',
            'shipped_at' => now(),
        ])->save();

        return back()->with('success', 'Order shipped successfully!');
    }

    // Receive order -> increase stock at requesting branch via Stock API
    public function receive(Order $order, StockService $stockApi)
    {
        if ($order->status !== 'shipped') {
            return back()->with('error', 'Order must be shipped before receiving');
        }

        if (auth()->user()->branch_id !== $order->requesting_branch_id && auth()->user()->role !== 'admin') {
            abort(403, 'Only the requesting branch can receive this order.');
        }

        try {
            $items = $order->items->map(fn($i) => [
                'product_id' => $i->product_id,
                'quantity'   => (int)$i->quantity,
            ])->values()->all();

            $stockApi->receive($order->id, $order->requesting_branch_id, $items);

            $order->fill([
                'status'      => 'received',
                'received_at' => now(),
            ])->save();

            return back()->with('success', 'Order received and stock updated!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to receive order: '.$e->getMessage());
        }
    }

    // Cancel order -> if approved, release reservation at supplier
    public function cancel(Order $order, StockService $stockApi)
    {
        if (!in_array($order->status, ['pending', 'approved'])) {
            return back()->with('error', 'Cannot cancel shipped or received orders');
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

            return back()->with('success', 'Order cancelled successfully!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to cancel order: '.$e->getMessage());
        }
    }

    // Branches with stock for a product (API-driven)
    public function getBranchesWithStock(Request $request, StockService $stockApi)
    {
        $request->validate([
            'product_id' => 'required|integer',
        ]);

        $currentBranchId = auth()->user()->branch_id ?? null;

        try {
            $rows = $stockApi->availability((int)$request->product_id, null);
            return response()->json($rows);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Stock service unavailable'], 502);
        }
    }
}
