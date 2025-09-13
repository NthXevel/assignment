<?php
namespace App\Services;

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
}
