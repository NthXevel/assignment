<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Branch;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Only admin and branch_manager can access user management
        $this->middleware(function ($request, $next) {
            if (!in_array(auth()->user()->role, ['admin', 'branch_manager'])) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        })->except(['profile', 'updateProfile']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = User::with('branch');

        if ($user->role === 'admin') {
            // Admin can filter across all branches
            $query->when($request->branch, function ($q, $branch) {
                $q->where('branch_id', $branch);
            });
        } elseif ($user->role === 'branch_manager') {
            // Branch managers can only see their branch
            $query->where('branch_id', $user->branch_id);
        }

        $query->when($request->role, function ($q, $role) {
            $q->where('role', $role);
        })->when($request->search, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        });

        $users = $query->paginate(15);

        $branches = $user->role === 'admin' ? Branch::all() : Branch::where('id', $user->branch_id)->get();
        $roles = ['admin', 'branch_manager', 'stock_manager', 'order_creator'];

        return view('users.index', compact('users', 'branches', 'roles'));
    }

    public function create()
    {
        $user = auth()->user();

        if ($user->role === 'branch_manager') {
            $branches = Branch::where('id', $user->branch_id)->get();
            $roles = ['order_creator', 'stock_manager']; // restricted roles
        } else {
            $branches = Branch::all();
            $roles = ['admin', 'branch_manager', 'stock_manager', 'order_creator'];
        }

        return view('users.create', compact('branches', 'roles'));
    }

    public function store(Request $request)
    {
        $currentUser = auth()->user();

        // Validation
        $validated = $request->validate([
            'username' => 'required|string|unique:users|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|string|min:8|confirmed',
            'branch_id' => 'required|exists:branches,id',
            'role' => 'required|in:admin,stock_manager,order_creator,branch_manager',
        ]);

        // Branch Manager restrictions
        if ($currentUser->role === 'branch_manager') {
            if ($validated['branch_id'] != $currentUser->branch_id) {
                return back()->with('error', 'You can only add users to your own branch');
            }
            if (!in_array($validated['role'], ['order_creator', 'stock_manager'])) {
                return back()->with('error', 'You can only create order creators or stock managers');
            }
        }

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        return redirect()->route('users.show', $user)
            ->with('success', 'User created successfully');
    }

    public function edit(User $user)
    {
        $currentUser = auth()->user();

        if ($currentUser->role === 'branch_manager') {
            if ($user->branch_id !== $currentUser->branch_id) {
                abort(403, 'You can only edit users in your own branch');
            }
            if (!in_array($user->role, ['order_creator', 'stock_manager'])) {
                abort(403, 'You can only edit stock managers or order creators');
            }

            $branches = Branch::where('id', $currentUser->branch_id)->get();
            $roles = ['order_creator', 'stock_manager'];
        } else {
            $branches = Branch::all();
            $roles = ['admin', 'branch_manager', 'stock_manager', 'order_creator'];
        }

        return view('users.edit', compact('user', 'branches', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $currentUser = auth()->user();

        // Branch Manager restrictions
        if ($currentUser->role === 'branch_manager') {
            if ($user->branch_id !== $currentUser->branch_id) {
                return back()->with('error', 'You can only edit users in your own branch');
            }
            if (!in_array($user->role, ['order_creator', 'stock_manager'])) {
                return back()->with('error', 'You can only edit order creators or stock managers');
            }
        }

        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'branch_id' => 'required|exists:branches,id',
            'role' => 'required|in:admin,branch_manager,stock_manager,order_creator',
        ]);

        if ($request->password) {
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $currentUser = auth()->user();

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Cannot delete your own account');
        }

        if ($currentUser->role === 'branch_manager') {
            if ($user->branch_id !== $currentUser->branch_id) {
                return back()->with('error', 'You can only delete users from your own branch');
            }
            if (!in_array($user->role, ['order_creator', 'stock_manager'])) {
                return back()->with('error', 'You can only delete stock managers and order creators');
            }
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
