<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BranchService
{
    protected string $baseUrl;
    protected int $timeout = 5;

    public function __construct()
    {
        $this->baseUrl = config('services.branches.base_url', config('app.url'));
    }

    public function listActive(): array
    {
        return Cache::remember('branches.active.v1', now()->addMinutes(5), function () {
            $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(5)
                ->get($this->baseUrl.'/api/branches', ['status' => 'active']);
            if (!$res->ok()) throw new \RuntimeException('Branch service unavailable');
            return $res->json();
        });
    }

    public function get(int $branchId): array
    {
        $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(1)
            ->get($this->baseUrl.'/api/branches/'.$branchId);
        if ($res->status() === 404) throw new \InvalidArgumentException('Branch not found');
        if (!$res->ok()) throw new \RuntimeException('Branch service unavailable');
        return $res->json();
    }
}
