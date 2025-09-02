<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\Order;
use App\Models\Branch;
use App\Models\ProductCategory;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Landing page for reports
    public function index()
    {
        return view('reports.index');
    }

    // Stock report
    public function stock(Request $request)
    {
        $query = Stock::with(['product.category', 'branch']);
        
        if (!auth()->user()->hasPermission('*')) {
            $query->where('branch_id', auth()->user()->branch_id);
        }
        
        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->category_id) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }
        
        $stocks = $query->get();
        $branches = Branch::all();
        $categories = ProductCategory::all();
        
        // Calculate totals
        $totalValue = $stocks->sum(function($stock) {
            return $stock->quantity * $stock->product->cost_price;
        });
        
        $lowStockCount = $stocks->where('quantity', '<=', function($stock) {
            return $stock->minimum_threshold;
        })->count();
        
        return view('reports.stock', compact('stocks', 'branches', 'categories', 'totalValue', 'lowStockCount'));
    }

    // Orders report
    public function orders(Request $request)
    {
        $query = Order::with(['requestingBranch', 'supplyingBranch', 'items.product']);
        
        if (!auth()->user()->hasPermission('*')) {
            $query->where(function($q) {
                $q->where('requesting_branch_id', auth()->user()->branch_id)
                  ->orWhere('supplying_branch_id', auth()->user()->branch_id);
            });
        }
        
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        $stats = [
            'total_orders' => $orders->count(),
            'total_value' => $orders->sum('total_amount'),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'completed_orders' => $orders->where('status', 'received')->count(),
        ];
        
        return view('reports.orders', compact('orders', 'stats'));
    }

    // Sales report
    public function sales(Request $request)
    {
        $query = Order::where('status', 'received')
                     ->with(['requestingBranch', 'items.product.category']);
        
        if ($request->from_date) {
            $query->whereDate('received_at', '>=', $request->from_date);
        }
        
        if ($request->to_date) {
            $query->whereDate('received_at', '<=', $request->to_date);
        }
        
        $completedOrders = $query->get();
        
        $analytics = [
            'total_transfers' => $completedOrders->count(),
            'total_value' => $completedOrders->sum('total_amount'),
            'average_order_value' => $completedOrders->avg('total_amount'),
            'top_products' => $completedOrders->flatMap->items
                                            ->groupBy('product.name')
                                            ->map->sum('quantity')
                                            ->sortDesc()
                                            ->take(10),
            'branch_performance' => $completedOrders->groupBy('requestingBranch.name')
                                                  ->map->sum('total_amount')
                                                  ->sortDesc(),
        ];
        
        return view('reports.sales', compact('completedOrders', 'analytics'));
    }
}
