<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;

class BranchApiController extends Controller
{
    // GET /api/branches?status=active
    public function index(Request $request)
    {
        $q = Branch::query();
        if ($request->get('status') === 'active') {
            $q->where('status', 'active');
        }
        return response()->json(
            $q->orderBy('name')->get(['id','name','status'])->toArray()
        );
    }

    // GET /api/branches/{id}
    public function show(int $id)
    {
        $b = Branch::find($id);
        if (!$b) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json(['id'=>$b->id,'name'=>$b->name,'status'=>$b->status]);
    }
}
