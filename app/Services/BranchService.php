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

    public function paginate(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $query = array_merge($filters, [
            'page'         => $page,
            'per_page'     => $perPage,
            'with_counts'  => 1,      // <-- ask for counts
        ]);
        $res = Http::timeout($this->timeout)->acceptJson()->get($this->baseUrl.'/api/branches', $query);
        if (!$res->successful()) $this->throw($res);
        return $res->json(); // {data,total,per_page,current_page}
    }

    public function listActive(): array
    {
        $res = \Http::retry(2, 150)
            ->timeout($this->timeout)
            ->connectTimeout(5)
            ->acceptJson()
            ->get($this->baseUrl.'/api/branches', ['status' => 'active']);

        if (!$res->ok()) {
            throw new \RuntimeException('Branch service unavailable');
        }

        $json = $res->json();

        // Handle both shapes: {data:[...]} OR [...]
        $rows = is_array($json) && array_key_exists('data', $json) && is_array($json['data'])
            ? $json['data']
            : (is_array($json) ? $json : []);

        // Normalize fields we actually use
        return array_map(static function ($b) {
            return [
                'id'       => (int)($b['id'] ?? 0),
                'name'     => (string)($b['name'] ?? ''),
                'status'   => (string)($b['status'] ?? ''),
                'is_main'  => (bool)($b['is_main'] ?? false),
                'location' => $b['location'] ?? null,
            ];
        }, $rows);
    }

    public function get(int $branchId): array
    {
        $res = Http::retry(2, 150)->timeout($this->timeout)->connectTimeout(5)
            ->get($this->baseUrl.'/api/branches/'.$branchId);
        if ($res->status() === 404) throw new \InvalidArgumentException('Branch not found');
        if (!$res->ok()) throw new \RuntimeException('Branch service unavailable');
        return $res->json();
    }

    public function create(array $data): array
    {
        $res = Http::timeout($this->timeout)->acceptJson()->post($this->baseUrl.'/api/branches', $data);
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    public function update(int $id, array $data): array
    {
        $res = Http::timeout($this->timeout)->acceptJson()->put($this->baseUrl."/api/branches/{$id}", $data);
        if (!$res->successful()) $this->throw($res);
        return $res->json();
    }

    public function deactivate(int $id): void
    {
        $res = Http::timeout($this->timeout)->acceptJson()->delete($this->baseUrl."/api/branches/{$id}");
        if (!$res->successful()) $this->throw($res);
    }

    public function mainBranchId(): int
    {
        $id = 0;
        $res = Http::timeout($this->timeout)->acceptJson()->get($this->baseUrl.'/api/branches/main');
        if ($res->ok()) {
            $id = (int) ($res->json('id') ?? 0);
            if ($id > 0) {
                return $id;
            }
        }
        if ($id <= 0) throw new \RuntimeException('Unable to resolve main branch id');
        return $id;
    }

    private function throw($res): void
    {
        throw new \RuntimeException("Branches API {$res->status()}: ".mb_substr((string)$res->body(), 0, 400));
    }

}
