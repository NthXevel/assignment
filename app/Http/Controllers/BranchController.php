<?php
// Author: Clive Lee Ee Xuan
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\BranchService;
use App\Services\ProductService;
use App\Services\StockService;
use App\Services\OrderService;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== 'admin') abort(403, 'Unauthorized action.');
            return $next($request);
        })->only(['create','store','edit','update','destroy']);
    }

    public function index(Request $request, BranchService $branchesApi)
    {
        $page = (int)$request->get('page', 1);
        $per  = 10;
        $resp = $branchesApi->paginate(['status'=>'active'], $page, $per);

        // Cast each row to an object so Blade can use ->id, ->name, etc.
        $items = array_map(fn ($row) => (object) $row, $resp['data'] ?? []);

        $branches = new LengthAwarePaginator(
            $items,
            $resp['total'] ?? count($items),
            $resp['per_page'] ?? $per,
            $resp['current_page'] ?? $page,
            ['path'=>$request->url(),'query'=>$request->query()]
        );

        $user = auth()->user();
        return view('branches.index', compact('branches','user'));
    }

    public function create() { return view('branches.create'); }

    public function store(
        Request $request,
        BranchService $branchesApi,
        ProductService $productsApi,
        StockService $stockApi
    ) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        try {
            // 1) Create branch via Branch API
            $branch = $branchesApi->create([
                'name' => $validated['name'],
                'location' => $validated['location'],
                'status' => 'active',
                'is_main' => false,
            ]);
            $branchId = (int)($branch['id'] ?? $branch['data']['id'] ?? 0);

            // 2) Init stock rows for all products (REST orchestration)
            $productIds = $productsApi->listActiveIds(); // [1,2,3,...]
            foreach ($productIds as $pid) {
                try {
                    $stockApi->upsert($branchId, (int)$pid, 0, 10, true);
                } catch (\Throwable $e) {
                    
                }
            }

            return redirect()->route('branches.index')
                ->with('success', 'Branch created successfully with stock initialized.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error'=>'Failed to create branch: '.$e->getMessage()]);
        }
    }

    public function show(int $id, BranchService $branchesApi)
    {
        $branch = $branchesApi->get($id);
        return view('branches.show', compact('branch'));
    }

    public function edit(int $id, BranchService $branchesApi)
    {
        $branch = $branchesApi->get($id);
        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, int $id, BranchService $branchesApi)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        try {
            $branchesApi->update($id, $validated);
            return redirect()->route('branches.index')->with('success', 'Branch updated successfully');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error'=>'Failed to update branch: '.$e->getMessage()]);
        }
    }

    public function destroy(
        int $id,
        BranchService $branchesApi,
        ProductService $productsApi,
        StockService $stockApi,
        OrderService $orderApi
    ) {
        // Main branch cannot be deactivated
        try {
            $mainId = $branchesApi->mainBranchId();
            if ($id === $mainId) {
                return back()->withErrors(['error' => 'The main branch cannot be deactivated.']);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Cannot determine main branch.']);
        }

        // Build a price map for unit_price (selling_price)
        $priceById = [];
        try {
            $activeProducts = collect($productsApi->listActive()); // [{id,name,category_name,selling_price}]
            $priceById = $activeProducts
                ->mapWithKeys(fn ($p) => [
                    (int)($p['id'] ?? 0) => (float)($p['selling_price'] ?? 0.0)
                ])->all();
        } catch (\Throwable $e) {
            // If price map fails, still proceed (unit_price will default to 0.0)
            \Log::warning('Could not load product prices for branch deactivation; defaulting to 0.0', [
                'branch_id' => $id, 'err' => $e->getMessage()
            ]);
        }

        // 1) For every product that has stock at this branch, return it to main via Orders API
        $productIds = $productsApi->listActiveIds();
        $errors = [];

        foreach ($productIds as $pid) {
            try {
                $rows = $stockApi->availability((int)$pid, null); // all branches
            } catch (\Throwable $e) {
                $errors[] = "Stock check failed for product {$pid}";
                continue;
            }
            $row = collect($rows)->firstWhere('branch_id', $id);
            $qty = (int)($row['available_quantity'] ?? 0);
            if ($qty <= 0) continue;

            $unitPrice = round((float)($priceById[(int)$pid] ?? 0.0), 2);

            try {
                $orderApi->createReturn(
                    fromBranchId: $id,
                    toBranchId:   $mainId,
                    items: [[
                        'product_id' => (int)$pid,
                        'quantity'   => $qty,
                        'unit_price' => $unitPrice,
                    ]],
                    notes: 'Auto return caused by branch deactivation',
                    createdBy: auth()->id(),
                    autoComplete: true
                );
            } catch (\Throwable $e) {
                $errors[] = "Return failed for product {$pid}: ".$e->getMessage();
            }
        }

        if (!empty($errors)) {
            return back()->withErrors(['error' => 'Some returns failed: '.implode(' | ', $errors)]);
        }

        // 2) Deactivate the branch
        try {
            $branchesApi->deactivate($id);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed to deactivate branch: '.$e->getMessage()]);
        }

        return redirect()->route('branches.index')
            ->with('success', 'Branch deactivated. All stock returned to main branch.');
    }
}
