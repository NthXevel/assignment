<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UserService
{
    private string $base;
    private int $timeout = 10;

    public function __construct()
    {
        $this->base = config('services.users.base_url', config('app.url'));
    }

    public function paginate(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = array_merge($filters, ['page' => $page, 'per_page' => $perPage]);
        $res = Http::timeout($this->timeout)->acceptJson()->get($this->base.'/api/users', $query);
        if (!$res->successful()) $this->throw($res);
        return $res->json(); // expect {data, total, per_page, current_page}
    }

    public function get(int $id): array
    {
        $res = Http::timeout($this->timeout)->acceptJson()->get($this->base."/api/users/{$id}");
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    public function create(array $data): array
    {
        $res = Http::timeout($this->timeout)->acceptJson()->post($this->base.'/api/users', $data);
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    public function update(int $id, array $data): array
    {
        $res = Http::timeout($this->timeout)->acceptJson()->put($this->base."/api/users/{$id}", $data);
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    // Soft-deactivate (the API should enforce “at least one manager” rule)
    public function deactivate(int $id): void
    {
        $res = Http::timeout($this->timeout)->acceptJson()->delete($this->base."/api/users/{$id}");
        if (!$res->successful()) $this->throw($res);
    }

    public function me(): array
    {
        $res = Http::timeout($this->timeout)->acceptJson()->get($this->base.'/api/me');
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    private function throw($res): void
    {
        throw new \RuntimeException("Users API {$res->status()}: ".mb_substr((string)$res->body(), 0, 400));
    }
}
