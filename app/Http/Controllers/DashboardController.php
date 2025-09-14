<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Order;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use App\Services\ProductService;
use App\Services\StockService;
use App\Services\OrderService;
use App\Services\BranchService;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index(
        ProductService $productsApi,
        StockService   $stockApi,
        OrderService   $ordersApi,
        BranchService  $branchesApi)
    {
        $user = auth()->user();
        
        $totalProducts = count($productsApi->listActive());
        $branchesPage  = $branchesApi->paginate(['status'=>'active'], 1, 1);
        $totalBranches = (int)($branchesPage['total'] ?? 0);

        $lowStockCount = (int)($stockApi->list(['branch_id'=>$user->branch_id, 'low_stock'=>1], 1, 1)['total'] ?? 0);
        $pendingOrders = (int)($ordersApi->paginate(['status'=>'pending'], 1, 1)['total'] ?? 0);
        
        $stats = [
            'total_products' => $totalProducts,
            'low_stock_items'=> $lowStockCount,
            'pending_orders' => $pendingOrders,
            'total_branches' => $totalBranches,
        ];
        
        // Branch-specific stats (only when user is branch-scoped)
        if (!method_exists($user, 'hasPermission') || !$user->hasPermission('*')) {
            $stats['branch_stock_value'] = $stockApi->branchValue((int)$user->branch_id);

            $stats['branch_orders_this_month'] = (int)($ordersApi->paginate([
                'branch_id' => $user->branch_id,
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to'   => now()->endOfMonth()->toDateString(),
            ], 1, 1)['total'] ?? 0);
        }
        
        // Recent orders (limit 5)
        $recent = $ordersApi->paginate(
            ($user->role === 'admin') ? [] : ['branch_id'=>$user->branch_id],
            1, 5
        )['data'] ?? [];
        
        // Low stock items list (limit 5)
        $lowList = $stockApi->list([
            'branch_id' => $user->branch_id,
            'low_stock' => 1,
            'sort'      => 'quantity_asc',
        ], 1, 5)['data'] ?? [];

        $branchNames = collect($branchesApi->listActive())
        ->mapWithKeys(fn ($b) => [(int)($b['id'] ?? 0) => (string)($b['name'] ?? '-')])
        ->all();

        $recentOrders  = collect($recent)->map(fn($r) => (object)$r);
        $lowStockItems = collect($lowList)->map(fn($r) => (object)$r);

        return view('dashboard', compact('stats', 'recentOrders', 'lowStockItems', 'branchNames'));
    }
}

