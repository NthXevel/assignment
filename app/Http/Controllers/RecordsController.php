<?php
// Author: Lee Kai Yi
namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\StockService;
use App\Services\OrderService;
use App\Services\BranchService;
use Illuminate\Pagination\LengthAwarePaginator;

class RecordsController extends Controller
{
    public function index(Request $request, StockService $stockApi, OrderService $ordersApi)
    {
        $search = $request->input('search');
        $reason = $request->input('reason');

        // -------- Stock Movements (via API) --------
        $movFilters = [];
        if ($reason) $movFilters['reason'] = $reason;   // substring match on API side
        if ($search) $movFilters['search'] = $search;

        $movResp = $stockApi->movements($movFilters, (int)$request->get('stocks_page', 1), 10);

        $stockMovements = new LengthAwarePaginator(
            collect($movResp['data'] ?? [])->map(fn($r) => (object)$r),
            (int)($movResp['total'] ?? 0),
            (int)($movResp['per_page'] ?? 10),
            (int)($movResp['current_page'] ?? 1),
            ['path' => $request->url(), 'pageName' => 'stocks_page']
        );

        // -------- Orders (received + canceled) via API --------
        $statuses = 'received,canceled';
        if ($reason && in_array(strtolower($reason), ['shipped','canceled','cancelled','received'])) {
            $statuses = (strtolower($reason) === 'cancelled') ? 'canceled' : strtolower($reason);
        }

        $orderFilters = ['statuses' => $statuses];
        if ($search) $orderFilters['search'] = $search;

        $ordResp = $ordersApi->paginate($orderFilters, (int)$request->get('orders_page', 1), 10);

        $orders = new LengthAwarePaginator(
            collect($ordResp['data'] ?? [])->map(fn($r) => (object)$r),
            (int)($ordResp['total'] ?? 0),
            (int)($ordResp['per_page'] ?? 10),
            (int)($ordResp['current_page'] ?? 1),
            ['path' => $request->url(), 'pageName' => 'orders_page']
        );

        return view('records.index', compact('stockMovements', 'orders'));
    }

    public function stock(Request $request, StockService $stockApi)
    {
        $filters = [];
        if ($r = $request->input('reason')) $filters['reason'] = $r;
        if ($s = $request->input('search')) $filters['search'] = $s;
        if ($b = $request->input('branch_id')) $filters['branch_id'] = (int)$b;

        $resp = $stockApi->movements($filters, (int)$request->get('page', 1), 10);

        $stockMovements = new LengthAwarePaginator(
            collect($resp['data'] ?? [])->map(fn($r) => (object)$r),
            (int)($resp['total'] ?? 0),
            (int)($resp['per_page'] ?? 10),
            (int)($resp['current_page'] ?? 1),
            ['path' => $request->url()]
        );

        return view('records.stock', compact('stockMovements'));
    }

    public function orders(Request $request, OrderService $ordersApi, BranchService $branchesApi)
    {
        // statuses: default to received,canceled
        $statuses = $request->filled('status')
            ? $request->input('status')
            : 'received,canceled';

        $filters = ['statuses' => $statuses];
        if ($s = $request->input('search')) $filters['search'] = $s;
        if ($b = $request->input('branch_id')) $filters['branch_id'] = (int)$b;

        $resp = $ordersApi->paginate($filters, (int)$request->get('page', 1), 10);

        $orders = new LengthAwarePaginator(
            collect($resp['data'] ?? [])->map(fn($r) => (object)$r),
            (int)($resp['total'] ?? 0),
            (int)($resp['per_page'] ?? 10),
            (int)($resp['current_page'] ?? 1),
            ['path' => $request->url()]
        );

        // Map branch names for display
        $branchNameMap = collect($branchesApi->listActive())
            ->mapWithKeys(fn($b) => [ (int)$b['id'] => (string)$b['name'] ])
            ->all();

        return view('records.orders', compact('orders', 'branchNameMap'));
    }
}
    