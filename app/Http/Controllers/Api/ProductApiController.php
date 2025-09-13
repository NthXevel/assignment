<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductApiController extends Controller
{
    // GET /api/products?status=active&fields=id,name,category_name,selling_price
    public function index(Request $request)
    {
        $status = $request->query('status', 'active');
        $fieldsParam = (string) $request->query('fields', 'id,name,selling_price,category_name');
        $want = collect(array_filter(array_map('trim', explode(',', $fieldsParam))));

        $includeCategory = $want->contains('category_name');

        $q = Product::query();
        if ($status === 'active') {
            // use the correct column in your schema: is_active or status
            if (\Schema::hasColumn('products', 'is_active')) {
                $q->where('is_active', 1);
            } elseif (\Schema::hasColumn('products', 'status')) {
                $q->where('status', 'active');
            }
        }

        // Always fetch the minimal base columns we may need
        $baseCols = ['id','name','selling_price','cost_price','category_id'];
        $rows = $q->when($includeCategory, fn($qq) => $qq->with('category'))
                  ->orderBy('name')
                  ->get($baseCols);

        // Shape the payload exactly as requested by "fields"
        $data = $rows->map(function ($p) use ($want, $includeCategory) {
            $out = [];
            foreach ($want as $f) {
                switch ($f) {
                    case 'id':             $out['id'] = $p->id; break;
                    case 'name':           $out['name'] = $p->name; break;
                    case 'selling_price':  $out['selling_price'] = $p->selling_price; break;
                    case 'cost_price':     $out['cost_price'] = $p->cost_price; break;
                    case 'category_name':  $out['category_name'] = $includeCategory ? optional($p->category)->name : null; break;
                }
            }
            return $out;
        });

        return response()->json($data->values()->all());
    }

    // GET /api/products/{id}
    public function show(int $id)
    {
        $p = Product::with('category')->find($id);
        if (!$p) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json([
            'id'            => $p->id,
            'name'          => $p->name,
            'category_name' => optional($p->category)->name,
            'selling_price' => $p->selling_price,
            'status'        => $p->status,
        ]);
    }

    // GET /api/products/bulk?ids=1,2,3
    public function bulk(Request $request)
    {
        $ids = collect(explode(',', (string)$request->query('ids')))
            ->filter()->map(fn($v) => (int)$v)->unique()->values()->all();
        if (empty($ids)) return response()->json([]);

        $rows = \App\Models\Product::with('category')
            ->whereIn('id', $ids)->get()->map(function ($p) {
                return [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'category_name' => optional($p->category)->name,
                    'selling_price' => $p->selling_price,
                ];
            });
        return response()->json($rows);
    }

}
