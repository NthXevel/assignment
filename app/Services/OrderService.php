<?php
// Author: Lee Kai Yi
namespace App\Services;

use Illuminate\Support\Facades\Http;

class OrderService
{
    private string $base;
    private int $timeout = 10;

    public function __construct()
    {
        $this->base = config('services.orders.base_url', config('app.url'));
    }

    public function createReturn(
        int $fromBranchId,
        int $toBranchId,
        array $items, // [['product_id'=>..,'quantity'=>..,'unit_price'=>..], ...]
        string $notes = '',
        ?int $createdBy = null,
        bool $autoComplete = true
    ): array {
        $payload = [
            'requesting_branch_id' => $toBranchId,   // main (receives)
            'supplying_branch_id'  => $fromBranchId, // source (sends)
            'items' => array_map(function ($i) {
                return [
                    'product_id' => (int) $i['product_id'],
                    'quantity'   => (int) $i['quantity'],
                    'unit_price' => isset($i['unit_price']) ? (float) $i['unit_price'] : 0.0,
                ];
            }, $items),
            'notes'         => $notes,
            'created_by'    => $createdBy,
            'auto_complete' => $autoComplete,
        ];

        $res = Http::timeout($this->timeout)->connectTimeout(10)
            ->acceptJson()->asJson()
            ->post($this->base.'/api/orders/return', $payload);

        if (!$res->successful()) {
            throw new \RuntimeException(
                'Order API error '.$res->status().': '.mb_substr((string) $res->body(), 0, 300)
            );
        }
        return $res->json();
    }

    // list orders
    public function paginate(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $query = array_merge($filters, ['page' => $page, 'per_page' => $perPage]);
        $res = Http::timeout($this->timeout)->acceptJson()->get($this->base.'/api/orders', $query);
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    public function show(int $id): array
    {
        $res = Http::timeout($this->timeout)->acceptJson()->get($this->base."/api/orders/{$id}");
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    // get single order
    public function get(int $id): array
    {
        $res = \Http::timeout($this->timeout)->acceptJson()->get($this->base."/api/orders/{$id}");
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    // create order
    public function create(array $payload): array
    {
        $res = \Http::timeout($this->timeout)->acceptJson()->post($this->base.'/api/orders', $payload);
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    // transitions
    public function approve(int $id): array
    {
        $res = \Http::timeout($this->timeout)->acceptJson()->post($this->base."/api/orders/{$id}/approve");
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    public function ship(int $id): array
    {
        $res = \Http::timeout($this->timeout)->acceptJson()->post($this->base."/api/orders/{$id}/ship");
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    public function receive(int $id): array
    {
        $res = \Http::timeout($this->timeout)->acceptJson()->post($this->base."/api/orders/{$id}/receive");
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    public function cancel(int $id): array
    {
        $res = \Http::timeout($this->timeout)->acceptJson()->post($this->base."/api/orders/{$id}/cancel");
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    private function throw($res): void
    {
        throw new \RuntimeException("Orders API {$res->status()}: ".mb_substr((string)$res->body(), 0, 400));
    }
}
