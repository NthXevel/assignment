<?php
// Author: Clive Lee Ee Xuan
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Branch;

class BranchApiController extends Controller
{
    /**
 * GET /api/branches
 * Query:
 *   status=active|inactive
 *   paginate=1 (or provide page/per_page)
 *   page=<int>&per_page=<int>
 *   with_counts=1   // include users_count & stocks_count
 *
 * Returns:
 *   - paginated: { data, total, per_page, current_page }
 *   - flat:      [ ...rows ]
 */
    public function index(Request $r)
    {
        $q = Branch::query();

        // Filter by status if provided
        if ($r->filled('status')) {
            $q->where('status', $r->get('status'));
        }

        // Optionally include counts
        if ($r->boolean('with_counts')) {
            $q->withCount([
                // active users only
                'users as users_count'  => fn($qq) => $qq->where('status', 'active'),
                // total stock rows for this branch
                'stocks as stocks_count',
            ]);
        }

        $select = ['id','name','status','is_main','location'];

        // Paginate if requested
        if ($r->boolean('paginate') || $r->filled('per_page') || $r->filled('page')) {
            $per  = max(1, (int) $r->get('per_page', 10));
            $page = max(1, (int) $r->get('page', 1));

            $p = $q->orderBy('name')->paginate($per, $select, 'page', $page);

            $items = $p->getCollection()->map(function ($b) {
                return [
                    'id'           => $b->id,
                    'name'         => $b->name,
                    'status'       => $b->status,
                    'is_main'      => (bool) $b->is_main,
                    'location'     => $b->location,
                    'users_count'  => $b->users_count  ?? null,
                    'stocks_count' => $b->stocks_count ?? null,
                ];
            })->values();

            return response()->json([
                'data'         => $items,
                'total'        => $p->total(),
                'per_page'     => $p->perPage(),
                'current_page' => $p->currentPage(),
            ]);
        }

        // Flat array (backwards compatible)
        $rows = $q->orderBy('name')->get($select)->map(function ($b) {
            return [
                'id'           => $b->id,
                'name'         => $b->name,
                'status'       => $b->status,
                'is_main'      => (bool) $b->is_main,
                'location'     => $b->location,
                'users_count'  => $b->users_count  ?? null,
                'stocks_count' => $b->stocks_count ?? null,
            ];
        })->values();

        return response()->json($rows);
    }

    /**
     * GET /api/branches/{id}
     */
    public function show(int $id)
    {
        $b = Branch::find($id);
        if (!$b) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'id'       => $b->id,
            'name'     => $b->name,
            'status'   => $b->status,
            'is_main'  => (bool)$b->is_main,
            'location' => $b->location,
        ]);
    }

    /**
     * GET /api/branches/main
     */
    public function main()
    {
        $b = Branch::where('is_main', true)->firstOrFail();
        return response()->json([
            'id'       => $b->id,
            'name'     => $b->name,
            'status'   => $b->status,
            'is_main'  => true,
            'location' => $b->location,
        ]);
    }

    /**
     * POST /api/branches
     * Body: name, location, (optional) status, is_main
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'name'     => ['required','string','max:255'],
            'location' => ['required','string','max:255'],
            'status'   => ['nullable', Rule::in(['active','inactive'])],
            'is_main'  => ['nullable','boolean'],
        ]);

        // Normalize to reduce accidental dupes from spaces/case
        $name     = trim($data['name']);
        $location = trim($data['location']);
        
        $branch = Branch::firstOrCreate(
            ['name' => $name, 'location' => $location],
            [
                'status'  => $data['status'] ?? 'active',
                'is_main' => (bool)($data['is_main'] ?? false),
            ]
        );

        $status = $branch->wasRecentlyCreated ? 201 : 200;

        return response()->json(['id' => $branch->id], $status);
    }

    /**
     * PUT /api/branches/{id}
     * Body: name, location (status/is_main changes are typically restricted)
     */
    public function update(Request $r, int $id)
    {
        $b = Branch::find($id);
        if (!$b) return response()->json(['error' => 'Not found'], 404);

        $data = $r->validate([
            'name'     => ['required','string','max:255'],
            'location' => ['required','string','max:255'],
            // If you wish to allow status/is_main edits via API, add validation here
        ]);

        $b->name     = $data['name'];
        $b->location = $data['location'];
        $b->save();

        return response()->json(['ok' => true]);
    }

    /**
     * DELETE /api/branches/{id}
     * Soft-deactivate branch. Disallow main branch.
     */
    public function destroy(int $id)
    {
        $b = Branch::find($id);
        if (!$b) return response()->json(['error' => 'Not found'], 404);

        if ($b->is_main) {
            return response()->json(['error' => 'Main branch cannot be deactivated'], 422);
        }

        $b->status = 'inactive';
        $b->save();

        return response()->json(['ok' => true]);
    }
}
