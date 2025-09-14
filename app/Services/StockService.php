<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class StockService
{
    protected string $baseUrl;
    protected int $timeout = 10;

    public function __construct()
    {
        $this->baseUrl = config('services.stock.base_url', config('app.url'));
    }

    public function availability(int $productId, ?int $excludeBranchId = null): array
    {
        $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(5)
            ->get($this->baseUrl.'/api/stock/availability', [
                'product_id'     => $productId,
                'exclude_branch' => $excludeBranchId,
            ]);
        if (!$res->ok()) throw new \RuntimeException('Stock service unavailable');
        return $res->json();
    }

    public function reserve(int $orderId, int $branchId, array $items): bool
    {
        $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(10)
            ->post($this->baseUrl.'/api/stock/reserve', [
                'order_id' => $orderId,
                'branch_id'=> $branchId,
                'items'    => $items,
            ]);
        if (!$res->ok()) throw new \RuntimeException($res->json('error') ?? 'Failed to reserve stock');
        return true;
    }

    public function release(int $orderId, int $branchId, array $items): bool
    {
        $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(10)
            ->post($this->baseUrl.'/api/stock/release', [
                'order_id' => $orderId,
                'branch_id'=> $branchId,
                'items'    => $items,
            ]);
        if (!$res->ok()) throw new \RuntimeException($res->json('error') ?? 'Failed to release stock');
        return true;
    }

    public function receive(int $orderId, int $branchId, array $items): bool
    {
        $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(10)
            ->post($this->baseUrl.'/api/stock/receive', [
                'order_id' => $orderId,
                'branch_id'=> $branchId,
                'items'    => $items,
            ]);
        if (!$res->ok()) throw new \RuntimeException($res->json('error') ?? 'Failed to receive stock');
        return true;
    }

    public function upsert(int $branchId, int $productId, int $qty, int $min, bool $preserveExisting = true): array
    {
        $res = Http::retry(2, 250)
            ->timeout($this->timeout ?? 10)
            ->connectTimeout(10)
            ->post($this->baseUrl.'/api/stock/upsert', [
                'product_id'        => $productId,
                'branch_id'         => $branchId,
                'quantity'          => $qty,
                'minimum_threshold' => $min,
                'preserve_existing' => $preserveExisting,
            ]);

        if (!$res->ok()) {
            throw new \RuntimeException('Stock service unavailable');
        }

        return $res->json();
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Stock::with(['product', 'branch'])
            ->whereHas('product', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('branch', function ($q) {
                $q->where('status', 'active');
            });

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['low_stock'])) {
            $query->whereColumn('quantity', '<=', 'minimum_threshold');
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Stock
    {
        return Stock::create($data);
    }

    public function update(Stock $stock, array $data): Stock
    {
        $oldQuantity = $stock->quantity;
        $stock->fill($data);
        $stock->save();

        if (array_key_exists('quantity', $data) && $data['quantity'] !== $oldQuantity) {
            StockMovement::create([
                'stock_id' => $stock->id,
                'quantity_change' => $stock->quantity - $oldQuantity,
                'reason' => 'API update',
                'balance_after' => $stock->quantity,
            ]);
        }

        return $stock->fresh(['product', 'branch']);
    }

    public function delete(Stock $stock): void
    {
        $stock->delete();
    }

    public function adjust(Stock $stock, int $amount, string $action, string $by = 'api'): Stock
    {
        $old = $stock->quantity;
        if ($action === 'increase') {
            $stock->quantity += $amount;
        } else {
            $stock->quantity = max(0, $stock->quantity - $amount);
        }
        $stock->save();

        StockMovement::create([
            'stock_id' => $stock->id,
            'quantity_change' => $stock->quantity - $old,
            'reason' => ucfirst($action) . " by {$amount} - {$by}",
            'balance_after' => $stock->quantity,
        ]);

        return $stock->fresh(['product', 'branch']);
    }

    public function updateQuantity(Stock $stock, int $newQuantity, string $by = 'api'): Stock
    {
        $old = $stock->quantity;
        $stock->quantity = $newQuantity;
        $stock->save();

        if ($old !== $newQuantity) {
            StockMovement::create([
                'stock_id' => $stock->id,
                'quantity_change' => $newQuantity - $old,
                'reason' => 'Direct edit by ' . $by,
                'balance_after' => $newQuantity,
            ]);
        }

        return $stock->fresh(['product', 'branch']);
    }

}



