<?php
// Author: Clive Lee Ee Xuan
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\UserService;
use App\Services\BranchService;


class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!in_array(auth()->user()->role, ['admin', 'branch_manager'])) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        })->except(['profile', 'updateProfile']);
    }

    public function index(Request $request, UserService $usersApi, BranchService $branchesApi)
    {
        $me = auth()->user();

        $filters = [
            'status' => 'active',
            'role'   => $request->role,
            'search' => $request->search,
        ];
        if ($me->role === 'branch_manager') {
            $filters['branch_id'] = $me->branch_id;
        } elseif ($request->filled('branch')) {
            $filters['branch_id'] = (int)$request->branch;
        }

        $page    = (int)($request->get('page', 1));
        $perPage = 15;
        $resp    = $usersApi->paginate($filters, $page, $perPage);

        // Build Laravel paginator from API payload
        $users = new LengthAwarePaginator(
            $resp['data'] ?? [],
            $resp['total'] ?? count($resp['data'] ?? []),
            $resp['per_page'] ?? $perPage,
            $resp['current_page'] ?? $page,
            ['path' => $request->url(), 'query'=>$request->query()]
        );

        // branches for filter dropdown
        $branches = ($me->role === 'admin')
            ? collect($branchesApi->listActive())
            : collect($branchesApi->listActive())->where('id', $me->branch_id)->values();

        $roles = ['admin', 'branch_manager', 'stock_manager', 'order_creator'];

        return view('users.index', compact('users', 'branches', 'roles'));
    }

    public function create(BranchService $branchesApi)
    {
        $me = auth()->user();
        $all = collect($branchesApi->listActive());

        if ($me->role === 'branch_manager') {
            $branches = $all->where('id', $me->branch_id)->values();
            $roles    = ['order_creator', 'stock_manager'];
        } else {
            $branches = $all;
            $roles    = ['admin', 'branch_manager', 'stock_manager', 'order_creator'];
        }

        return view('users.create', compact('branches', 'roles'));
    }

    public function store(Request $request, UserService $usersApi)
    {
        $me = auth()->user();

        $validated = $request->validate([
            'username'   => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'password'   => 'required|string|min:8|confirmed',
            'branch_id'  => 'required|integer|min:1',
            'role'       => 'required|in:admin,stock_manager,order_creator,branch_manager',
        ]);

        // Enforce branch_manager limits at UI; API should also enforce
        if ($me->role === 'branch_manager') {
            if ((int)$validated['branch_id'] !== (int)$me->branch_id) {
                return back()->with('error', 'You can only add users to your own branch');
            }
            if (!in_array($validated['role'], ['order_creator', 'stock_manager'])) {
                return back()->with('error', 'You can only create order creators or stock managers');
            }
        }

        // Send as-is; hashing is the Users API’s job
        try {
            $created = $usersApi->create($validated);
            return redirect()->route('users.show', $created['id'] ?? $created['data']['id'] ?? null)
                ->with('success', 'User created successfully');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error'=>$e->getMessage()]);
        }
    }

    public function edit(int $id, UserService $usersApi, BranchService $branchesApi)
    {
        $me    = auth()->user();
        $user  = $usersApi->get($id);
        $all   = collect($branchesApi->listActive());

        if ($me->role === 'branch_manager') {
            if ((int)$user['branch_id'] !== (int)$me->branch_id ||
                !in_array($user['role'], ['order_creator','stock_manager'])) {
                abort(403, 'You can only edit stock managers or order creators in your branch');
            }
            $branches = $all->where('id', $me->branch_id)->values();
            $roles    = ['order_creator','stock_manager'];
        } else {
            $branches = $all;
            $roles    = ['admin','branch_manager','stock_manager','order_creator'];
        }

        return view('users.edit', compact('user','branches','roles'));
    }

    public function update(Request $request, int $id, UserService $usersApi)
    {
        $me = auth()->user();
        $user = $usersApi->get($id);

        if ($me->role === 'branch_manager') {
            if ((int)$user['branch_id'] !== (int)$me->branch_id ||
                !in_array($user['role'], ['order_creator','stock_manager'])) {
                return back()->with('error', 'You can only edit users in your branch (OC/SM)');
            }
        }

        $validated = $request->validate([
            'username'  => 'required|string|max:255',
            'email'     => 'required|email|max:255',
            'branch_id' => 'required|integer|min:1',
            'role'      => 'required|in:admin,branch_manager,stock_manager,order_creator',
            'password'  => 'nullable|string|min:8', // if provided, API hashes
        ]);

        try {
            $usersApi->update($id, $validated);
            return redirect()->route('users.index')->with('success', 'User updated successfully');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error'=>$e->getMessage()]);
        }
    }

    public function show(int $id, UserService $usersApi)
    {
        $user = $usersApi->get($id);
        return view('users.show', compact('user'));
    }

    public function destroy(int $id, UserService $usersApi)
    {
        $me = auth()->user();
        if ($id === $me->id) {
            return back()->with('error', 'Cannot deactivate your own account');
        }

        try {
            $usersApi->deactivate($id); // API enforces “at least 1 manager/branch”
            return redirect()->route('users.index')->with('success', 'User deactivated successfully');
        } catch (\Throwable $e) {
            return back()->withErrors(['error'=>$e->getMessage()]);
        }
    }

    // These can remain local (profile is tied to the current app’s auth)
    public function profile() { $user = auth()->user(); return view('users.profile', compact('user')); }
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,username,'.$user->id,
            'email'    => 'required|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
        ]);
        if (!empty($validated['password'])) $user->password = \Hash::make($validated['password']);
        $user->username = $validated['username'];
        $user->email    = $validated['email'];
        $user->save();
        return back()->with('success','Profile updated successfully');
    }
}
