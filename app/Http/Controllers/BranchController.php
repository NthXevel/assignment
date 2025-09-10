<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Only admin can manage branches
        $this->middleware('permission:manage_branches')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a list of branches based on user role
     */
    public function index()
    {
        $user = auth()->user();

        // Everyone can see all branches
        $branches = Branch::withCount(['users', 'stocks'])->paginate(10);

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
            'status' => 'required|in:active,inactive',
            'is_main' => 'boolean',
        ]);

        // Ensure only one main branch exists
        if ($request->has('is_main') && Branch::where('is_main', true)->exists()) {
            return back()->withInput()
                ->with('error', 'A main branch already exists');
        }

        Branch::create([
            'name' => $validated['name'],
            'location' => $validated['location'],
            'status' => $validated['status'],
            'is_main' => $request->has('is_main'),
        ]);

        return redirect()->route('branches.index')
            ->with('success', 'Branch created successfully');
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
            'is_main' => 'boolean',
        ]);

        // Ensure only one main branch
        if ($validated['is_main'] && $branch->id !== Branch::where('is_main', true)->first()?->id) {
            if (Branch::where('is_main', true)->exists()) {
                return back()->with('error', 'A main branch already exists');
            }
        }

        $branch->update($validated);

        return redirect()->route('branches.show', $branch)
                         ->with('success', 'Branch updated successfully');
    }

    /**
     * Delete branch and related stocks (admin only)
     */
    public function destroy(Branch $branch)
    {
        if ($branch->is_main) {
            return back()->with('error', 'Cannot delete the main branch');
        }

        DB::transaction(function () use ($branch) {
            // Delete all related stocks
            Stock::where('branch_id', $branch->id)->delete();

            // Delete the branch
            $branch->delete();
        });

        return redirect()->route('branches.index')
                         ->with('success', 'Branch and all related stocks deleted successfully');
    }
}
