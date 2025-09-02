<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage_branches');
    }
    
    public function index()
    {
        $branches = Branch::withCount(['users', 'stocks'])->paginate(10);
        return view('branches.index', compact('branches'));
    }
    
    public function create()
    {
        return view('branches.create');
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'is_main' => 'boolean',
        ]);
        
        // Ensure only one main branch
        if ($validated['is_main'] && Branch::where('is_main', true)->exists()) {
            return back()->with('error', 'A main branch already exists');
        }
        
        $branch = Branch::create($validated);
        
        return redirect()->route('branches.show', $branch)
                        ->with('success', 'Branch created successfully');
    }
    
    public function show(Branch $branch)
    {
        $branch->load(['users', 'stocks.product']);
        return view('branches.show', compact('branch'));
    }
    
    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }
    
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
    
    public function destroy(Branch $branch)
    {
        if ($branch->is_main) {
            return back()->with('error', 'Cannot delete the main branch');
        }
        
        if ($branch->users()->exists()) {
            return back()->with('error', 'Cannot delete branch with users');
        }
        
        if ($branch->stocks()->sum('quantity') > 0) {
            return back()->with('error', 'Cannot delete branch with stock');
        }
        
        $branch->delete();
        
        return redirect()->route('branches.index')
                        ->with('success', 'Branch deleted successfully');
    }
}

