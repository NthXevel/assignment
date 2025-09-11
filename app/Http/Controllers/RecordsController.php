<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Order;
use Illuminate\Http\Request;

class RecordsController extends Controller
{
    public function index(Request $request)
    {
        // query param values
        $search = $request->input('search');
        $reason = $request->input('reason');

        // --------------- Stock Movements query ---------------
        $stockQuery = StockMovement::with(['stock.product', 'stock.branch'])->latest();

        if ($reason) {
            // only apply reason filter for stock movements if it matches stock reasons
            // (this will simply apply to the reason column on stock_movements)
            $stockQuery->where('reason', $reason);
        }

        if ($search) {
            // search by product name (via the related stock -> product)
            $stockQuery->whereHas('stock.product', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        // paginate stock movements; use a custom page name so it doesn't conflict with orders pagination
        $stockMovements = $stockQuery->paginate(10, ['*'], 'stocks_page')->appends($request->query());

        // --------------- Orders query (only shipped + canceled) ---------------
        $orderQuery = Order::with(['requestingBranch', 'supplyingBranch'])
            ->whereIn('status', ['received', 'canceled'])
            ->latest();

        if ($search) {
            // support searching orders by order_number or by branch name
            $orderQuery->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                  ->orWhereHas('requestingBranch', function ($qq) use ($search) {
                      $qq->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('supplyingBranch', function ($qq) use ($search) {
                      $qq->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        // if reason is provided and matches order statuses, filter orders by status
        if ($reason && in_array(strtolower($reason), ['shipped', 'canceled', 'cancelled'])) {
            // normalize possible 'cancelled' spelling
            $status = strtolower($reason) === 'cancelled' ? 'canceled' : strtolower($reason);
            $orderQuery->where('status', $status);
        }

        $orders = $orderQuery->paginate(10, ['*'], 'orders_page')->appends($request->query());

        // Return both variables that your Blade expects
        return view('records.index', compact('stockMovements', 'orders'));
    }
}
    