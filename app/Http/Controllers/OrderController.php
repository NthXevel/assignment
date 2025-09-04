<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;  
use App\Models\Branch; 
use App\Models\Product; 
use App\Models\Stock;   
use Illuminate\Support\Facades\DB;



class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = Order::with(['requestingBranch', 'supplyingBranch', 'creator'])
                     ->when(!$user->hasPermission('*'), function($q) use ($user) {
                         $q->where(function($subQ) use ($user) {
                             $subQ->where('requesting_branch_id', $user->branch_id)
                                  ->orWhere('supplying_branch_id', $user->branch_id);
                         });
                     })
                     ->when($request->status, function($q, $status) {
                         $q->where('status', $status);
                     })
                     ->when($request->priority, function($q, $priority) {
                         $q->where('priority', $priority);
                     });
        
        $orders = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return view('orders.index', compact('orders'));
    }
    
    public function create()
    {
        $branches = Branch::where('id', '!=', auth()->user()->branch_id)->get();
        $products = Product::with('category')->where('is_active', true)->get();
        $mainBranch = Branch::getMainBranch();
        
        return view('orders.create', compact('branches', 'products', 'mainBranch'));
    }
    
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
        
        $order = Order::create([
            'requesting_branch_id' => auth()->user()->branch_id,
            'supplying_branch_id' => $validated['supplying_branch_id'],
            'created_by' => auth()->id(),
            'priority' => $validated['priority'],
            'notes' => $validated['notes'],
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
        
        // Use Strategy Pattern for order processing
        $context = new OrderContext();
        
        if ($validated['priority'] === 'urgent') {
            $context->setStrategy(new UrgentOrderStrategy());
        } else {
            $context->setStrategy(new StandardOrderStrategy());
        }
        
        $context->processOrder($order);
        
        return redirect()->route('orders.show', $order)
                        ->with('success', 'Order created successfully');
    }
    
    public function show(Order $order)
    {
        $order->load(['requestingBranch', 'supplyingBranch', 'creator', 'items.product']);
        
        // Check if user has permission to view this order
        if (!auth()->user()->hasPermission('*') && 
            !in_array(auth()->user()->branch_id, [$order->requesting_branch_id, $order->supplying_branch_id])) {
            abort(403);
        }
        
        return view('orders.show', compact('order'));
    }
    
    public function approve(Order $order)
    {
        if (!auth()->user()->hasPermission('approve_orders')) {
            abort(403);
        }
        
        // Check stock availability
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
    
    public function ship(Order $order)
    {
        if ($order->status !== 'approved') {
            return back()->with('error', 'Order must be approved before shipping');
        }
        
        $order->ship();
        
        return back()->with('success', 'Order shipped successfully');
    }
    
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

