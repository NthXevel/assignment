<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use App\Models\Order;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== 'admin') {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        })->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a list of branches based on user role
     */
    public function index()
    {
        $user = auth()->user();

        // Only show branches with status = 'active'
        $branches = Branch::where('status', 'active')
            ->withCount(['users', 'stocks'])
            ->paginate(10);

        return view('branches.index', compact('branches', 'user'));
    }

    /**
     * Show form to create a new branch (admin only)
     */
    public function create()
    {
        return view('branches.create');
    }

    /**
     * Store a newly created branch
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        // Add default values for status and is_main
        $branchData = array_merge($validated, [
            'status' => 'active',
            'is_main' => false
        ]);

        try {
            DB::transaction(function () use ($branchData) {
                // Create the branch
                $branch = Branch::create($branchData);

                // Fetch all products
                $products = \App\Models\Product::all();

                // Create stock entries for each product with quantity = 0
                foreach ($products as $product) {
                    Stock::create([
                        'product_id' => $product->id,
                        'branch_id' => $branch->id,
                        'quantity' => 0,
                    ]);
                }
            });

            return redirect()
                ->route('branches.index')
                ->with('success', 'Branch created successfully with stock initialized for all products.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create branch: ' . $e->getMessage()]);
        }
    }


    /**
     * Show branch details
     */
    public function show(Branch $branch)
    {
        $branch->load(['users', 'stocks.product']);
        return view('branches.show', compact('branch'));
    }

    /**
     * Show form to edit a branch
     */
    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    /**
     * Update branch info
     */
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        try {
            // Preserve existing is_main and status values
            $validated['is_main'] = $branch->is_main;
            $validated['status'] = $branch->status;

            $branch->update($validated);

            return redirect()
                ->route('branches.index')
                ->with('success', 'Branch updated successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update branch: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete branch and related stocks (admin only)
     */
    public function destroy($id)
    {
        $branch = Branch::with(['stocks', 'users'])->findOrFail($id);

        if ($branch->is_main) {
            return back()->withErrors(['error' => 'The main branch cannot be deactivated.']);
        }

        DB::transaction(function () use ($branch) {
            // Get the main branch
            $mainBranch = Branch::where('is_main', true)->firstOrFail();

            foreach ($branch->stocks as $stock) {
                if ($stock->quantity > 0) {
                    // 1. Create a new order for this product
                    $order = Order::create([
                        'order_number' => 'ORD-RETURN-' . strtoupper(uniqid()),
                        'requesting_branch_id' => $mainBranch->id, // main branch receives
                        'supplying_branch_id' => $branch->id,    // closing branch supplies
                        'created_by' => auth()->id(),
                        'status' => 'received',     // auto-mark as received
                        'priority' => 'urgent',
                        'notes' => "Auto-return of {$stock->product->name} due to branch deactivation",
                    ]);

                    // 2. Add the single order item for this stock
                    $order->items()->create([
                        'product_id' => $stock->product_id,
                        'quantity' => $stock->quantity,
                        'unit_price' => $stock->product->selling_price ?? 0,
                        'total_price' => ($stock->product->selling_price ?? 0) * $stock->quantity,
                    ]);

                    // 3. Transfer stock to main branch
                    $mainStock = Stock::firstOrCreate(
                        [
                            'branch_id' => $mainBranch->id,
                            'product_id' => $stock->product_id,
                        ],
                        ['quantity' => 0]
                    );

                    $mainStock->increment('quantity', $stock->quantity);

                    // 4. Clear stock in closing branch
                    $stock->update(['quantity' => 0]);
                }
            }

            // 5. Deactivate all users in this branch
            $branch->users()->update(['status' => 'inactive']);

            // 6. Deactivate the branch
            $branch->update(['status' => 'inactive']);
        });

        return redirect()->route('branches.index')
            ->with('success', 'Branch deactivated. All stock returned to main branch via multiple orders. Users set inactive.');
    }
}
