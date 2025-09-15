<?php
// Author: Clive Lee Ee Xuan
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserApiController extends Controller
{
    /**
     * GET /api/users
     * Query params: status (active/inactive), branch_id, role, search, page, per_page
     * Returns: { data, total, per_page, current_page }
     */
    public function index(Request $r)
    {
        $q = User::query()->with('branch:id,name');

        // Filters
        if ($r->filled('status')) {
            $q->where('status', $r->get('status'));
        }
        if ($r->filled('branch_id')) {
            $q->where('branch_id', (int)$r->get('branch_id'));
        }
        if ($r->filled('role')) {
            $q->where('role', $r->get('role'));
        }
        if ($s = $r->get('search')) {
            $q->where(function ($sub) use ($s) {
                $sub->where('username', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $per  = (int)($r->get('per_page', 15));
        $page = (int)($r->get('page', 1));
        $p    = $q->orderBy('username')->paginate($per, ['*'], 'page', $page);

        return response()->json([
            'data'         => $p->items(),
            'total'        => $p->total(),
            'per_page'     => $p->perPage(),
            'current_page' => $p->currentPage(),
        ]);
    }

    /**
     * GET /api/users/{id}
     */
    public function show(int $id)
    {
        $u = User::with('branch:id,name')->find($id);
        if (!$u) return response()->json(['error' => 'Not found'], 404);
        return response()->json($u);
    }

    /**
     * POST /api/users
     * Body: username, email, password, branch_id, role
     * Creates *active* user. Password is hashed here.
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'username'  => ['required','string','max:255','unique:users,username'],
            'email'     => ['required','email','max:255','unique:users,email'],
            'password'  => ['required','string','min:8'],
            'branch_id' => ['required','integer','exists:branches,id'],
            'role'      => ['required', Rule::in(['admin','branch_manager','stock_manager','order_creator'])],
        ]);

        $u = new User();
        $u->username  = $data['username'];
        $u->email     = $data['email'];
        $u->password  = Hash::make($data['password']);
        $u->branch_id = (int)$data['branch_id'];
        $u->role      = $data['role'];
        $u->status    = 'active';
        $u->save();

        return response()->json(['id' => $u->id], 201);
    }

    /**
     * PUT /api/users/{id}
     * Can also change role/branch. If demoting the *last* branch_manager of a branch,
     * this returns 422.
     */
    public function update(Request $r, int $id)
    {
        $u = User::find($id);
        if (!$u) return response()->json(['error' => 'Not found'], 404);

        $data = $r->validate([
            'username'  => ['required','string','max:255', Rule::unique('users','username')->ignore($u->id)],
            'email'     => ['required','email','max:255', Rule::unique('users','email')->ignore($u->id)],
            'branch_id' => ['required','integer','exists:branches,id'],
            'role'      => ['required', Rule::in(['admin','branch_manager','stock_manager','order_creator'])],
            'password'  => ['nullable','string','min:8'],
        ]);

        // If this user is currently a branch_manager and is being moved/demoted,
        // prevent leaving the branch without any manager.
        $isDemotingOrMoving = ($u->role === 'branch_manager')
            && (($data['role'] !== 'branch_manager') || ((int)$data['branch_id'] !== (int)$u->branch_id));

        if ($isDemotingOrMoving) {
            $count = User::where('branch_id', $u->branch_id)
                ->where('role', 'branch_manager')
                ->where('status', 'active')
                ->where('id', '!=', $u->id)
                ->count();
            if ($count < 1) {
                return response()->json(['error' => 'Each branch must have at least one branch manager'], 422);
            }
        }

        $u->username  = $data['username'];
        $u->email     = $data['email'];
        $u->branch_id = (int)$data['branch_id'];
        $u->role      = $data['role'];
        if (!empty($data['password'])) {
            $u->password = Hash::make($data['password']);
        }
        $u->save();

        return response()->json(['ok' => true]);
    }

    /**
     * DELETE /api/users/{id}
     * Soft-deactivate user. If this is the last branch_manager in their branch, returns 422.
     */
    public function destroy(int $id)
    {
        $u = User::find($id);
        if (!$u) return response()->json(['error' => 'Not found'], 404);

        if ($u->role === 'branch_manager' && $u->status === 'active') {
            $count = User::where('branch_id', $u->branch_id)
                ->where('role', 'branch_manager')
                ->where('status', 'active')
                ->where('id', '!=', $u->id)
                ->count();
            if ($count < 1) {
                return response()->json(['error' => 'Each branch must have at least one branch manager'], 422);
            }
        }

        $u->status = 'inactive';
        $u->save();

        return response()->json(['ok' => true]);
    }

    /**
     * GET /api/me
     * Returns the authenticated user (can be use if using session/cookie auth).
     */
    public function me(Request $r)
    {
        $me = $r->user();
        if (!$me) return response()->json(['error'=>'Unauthenticated'], 401);
        return response()->json($me);
    }
}
