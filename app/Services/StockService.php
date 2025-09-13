<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class StockService
{
    protected string $baseUrl;
    protected int $timeout = 2;

    public function __construct()
    {
        $this->baseUrl = config('services.stock.base_url', config('app.url'));
    }

    public function availability(int $productId, ?int $excludeBranchId = null): array
    {
        $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(1)
            ->get($this->baseUrl.'/api/stock/availability', [
                'product_id'     => $productId,
                'exclude_branch' => $excludeBranchId,
            ]);
        if (!$res->ok()) throw new \RuntimeException('Stock service unavailable');
        return $res->json();
    }

    public function reserve(int $orderId, int $branchId, array $items): bool
    {
        $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(1)
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
        $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(1)
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
        $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(1)
            ->post($this->baseUrl.'/api/stock/receive', [
                'order_id' => $orderId,
                'branch_id'=> $branchId,
                'items'    => $items,
            ]);
        if (!$res->ok()) throw new \RuntimeException($res->json('error') ?? 'Failed to receive stock');
        return true;
    }

    public function upsert(int $branchId, int $productId, int $qty = 0, int $min = 0, bool $preserveExisting = true): array
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

}
