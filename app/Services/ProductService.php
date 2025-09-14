<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    protected string $baseUrl;
    protected int $timeout = 10;

    public function __construct()
    {
        $this->baseUrl = config('services.products.base_url', config('app.url'));
    }

    /** Cached 5 min to speed up dropdowns */
    public function listActive(): array
    {
        return Cache::remember('products.active.v1', now()->addMinutes(5), function () {
            $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(10)
                ->get($this->baseUrl.'/api/products', [
                    'status' => 'active',
                    'fields' => 'id,name,category_name,selling_price',
                ]);
            if (!$res->ok()) throw new \RuntimeException('Product service unavailable');
            return $res->json();
        });
    }

    public function get(int $productId): array
    {
        $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(1)
            ->get($this->baseUrl.'/api/products/'.$productId);
        if ($res->status() === 404) throw new \InvalidArgumentException('Product not found');
        if (!$res->ok()) throw new \RuntimeException('Product service unavailable');
        return $res->json();
    }

    /** Bulk fetch by IDs to avoid N+1 */
    public function bulkGet(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) return [];
        $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(1)
            ->get($this->baseUrl.'/api/products/bulk', ['ids' => implode(',', $ids)]);
        if (!$res->ok()) throw new \RuntimeException('Product service unavailable');
        return $res->json(); // [{id,name,category_name,selling_price}, ...]
    }
    
    // Minimal list to drive stock init
    public function listActiveIds(): array
    {
        $res = \Http::timeout($this->timeout)->acceptJson()
            ->get($this->baseUrl.'/api/products', ['status'=>'active','fields'=>'id']);
        if (!$res->successful()) {
            throw new \RuntimeException("Products API {$res->status()}: ".mb_substr((string)$res->body(),0,200));
        }
        // Accept either {data:[{id:..}]} or a flat array
        $rows = $res->json('data') ?? $res->json();
        return array_map(fn($r) => (int)$r['id'], $rows);
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Product::query()->with('category');

        if (!empty($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['category'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category']);
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function find(int $id): ?Product
    {
        return Product::with('category')->find($id);
    }

    public function create(array $data): Product
    {
        $product = new Product();
        $product->fill([
            'name' => $data['name'],
            'model' => $data['model'],
            'sku' => $data['sku'],
            'category_id' => $data['category_id'],
            'cost_price' => round((float) $data['cost_price'], 2),
            'selling_price' => round((float) $data['selling_price'], 2),
            'description' => $data['description'] ?? '',
            'specifications' => $data['specifications'] ?? [],
            'is_active' => $data['is_active'] ?? true,
        ]);
        $product->save();
        return $product->fresh('category');
    }

    public function update(Product $product, array $data): Product
    {
        $product->fill([
            'name' => $data['name'] ?? $product->name,
            'category_id' => $data['category_id'] ?? $product->category_id,
            'cost_price' => array_key_exists('cost_price', $data) ? round((float) $data['cost_price'], 2) : $product->cost_price,
            'selling_price' => array_key_exists('selling_price', $data) ? round((float) $data['selling_price'], 2) : $product->selling_price,
            'description' => $data['description'] ?? $product->description,
            'specifications' => $data['specifications'] ?? $product->specifications,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $product->is_active,
        ]);
        $product->save();
        return $product->fresh('category');
    }

    public function delete(Product $product): void
    {
        $product->update(['is_active' => false]);
    }
}
