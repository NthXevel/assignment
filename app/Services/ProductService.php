<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
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



