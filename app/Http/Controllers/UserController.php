<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage_users')->except(['profile', 'updateProfile']);
    }
    
    public function index(Request $request)
    {
        $query = User::with('branch')
                    ->when($request->branch, function($q, $branch) {
                        $q->where('branch_id', $branch);
                    })
                    ->when($request->role, function($q, $role) {
                        $q->where('role', $role);
                    })
                    ->when($request->search, function($q, $search) {
                        $q->where('username', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
        
        $users = $query->paginate(15);
        $branches = Branch::all();
        $roles = ['admin', 'stock_manager', 'order_creator', 'branch_manager'];
        
        return view('users.index', compact('users', 'branches', 'roles'));
    }
    
    public function create()
    {
        $branches = Branch::all();
        $roles = ['admin', 'stock_manager', 'order_creator', 'branch_manager'];
        
        return view('users.create', compact('branches', 'roles'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|string|min:8|confirmed',
            'branch_id' => 'required|exists:branches,id',
            'role' => 'required|in:admin,stock_manager,order_creator,branch_manager',
        ]);
        
        $validated['password'] = Hash::make($validated['password']);
        
        $user = User::create($validated);
        
        return redirect()->route('users.show', $user)
                        ->with('success', 'User created successfully');
    }
    
    public function show(User $user)
    {
        $user->load('branch');
        return view('users.show', compact('user'));
    }
    
    public function edit(User $user)
    {
        $branches = Branch::all();
        $roles = ['admin', 'stock_manager', 'order_creator', 'branch_manager'];
        
        return view('users.edit', compact('user', 'branches', 'roles'));
    }
    
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username,' . $user->id . '|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id . '|max:255',
            'branch_id' => 'required|exists:branches,id',
            'role' => 'required|in:admin,stock_manager,order_creator,branch_manager',
            'is_active' => 'boolean',
        ]);
        
        if ($request->password) {
            $validated['password'] = Hash::make($request->password);
        }
        
        $user->update($validated);
        
        return redirect()->route('users.show', $user)
                        ->with('success', 'User updated successfully');
    }
    
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete your own account');
        }
        
        if ($user->orders()->exists()) {
            return back()->with('error', 'Cannot delete user with order history');
        }
        
        $user->delete();
        
        return redirect()->route('users.index')
                        ->with('success', 'User deleted successfully');
    }
    
    public function profile()
    {
        $user = auth()->user()->load('branch');
        return view('users.profile', compact('user'));
    }
    
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username,' . $user->id . '|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id . '|max:255',
        ]);
        
        if ($request->password) {
            $validated['password'] = Hash::make($request->password);
        }
        
        $user->update($validated);
        
        return back()->with('success', 'Profile updated successfully');
    }
}
