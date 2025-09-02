<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Order;
use App\Models\Branch;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
        $user = auth()->user();
        
        $stats = [
            'total_products' => Product::where('is_active', true)->count(),
            'low_stock_items' => Stock::whereRaw('quantity <= minimum_threshold')->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'total_branches' => Branch::count(),
        ];
        
        // Branch-specific stats
        if (!$user->hasPermission('*')) {
            $stats['branch_stock_value'] = Stock::where('branch_id', $user->branch_id)
                ->join('products', 'stocks.product_id', '=', 'products.id')
                ->sum(DB::raw('stocks.quantity * products.cost_price'));
                
            $stats['branch_orders_this_month'] = Order::where('requesting_branch_id', $user->branch_id)
                ->whereMonth('created_at', now()->month)
                ->count();
        }
        
        // Recent activities
        $recentOrders = Order::with(['requestingBranch', 'supplyingBranch'])
            ->when(!$user->hasPermission('*'), function($q) use ($user) {
                $q->where(function($subQ) use ($user) {
                    $subQ->where('requesting_branch_id', $user->branch_id)
                         ->orWhere('supplying_branch_id', $user->branch_id);
                });
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        $lowStockItems = Stock::with(['product', 'branch'])
            ->whereRaw('quantity <= minimum_threshold')
            ->when(!$user->hasPermission('*'), function($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->orderBy('quantity', 'asc')
            ->limit(5)
            ->get();
        
        return view('dashboard', compact('stats', 'recentOrders', 'lowStockItems'));
    }
}
