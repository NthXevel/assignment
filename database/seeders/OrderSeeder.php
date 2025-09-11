<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        $users = User::all();

        if ($branches->count() < 2 || $users->isEmpty()) {
            $this->command->warn('Not enough branches or users to seed orders.');
            return;
        }

        foreach (range(1, 20) as $i) {
            $requesting = $branches->random();
            $supplying = $branches->where('id', '!=', $requesting->id)->random();

            Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(6)),
                'requesting_branch_id' => $requesting->id,
                'supplying_branch_id' => $supplying->id,
                'created_by' => $users->random()->id,
                'status' => collect(['pending', 'approved', 'shipped', 'received', 'cancelled'])->random(),
                'priority' => collect(['standard', 'urgent'])->random(),
                'notes' => fake()->sentence(),
                'approved_at' => rand(0, 1) ? now()->subDays(rand(1, 10)) : null,
                'shipped_at' => rand(0, 1) ? now()->subDays(rand(1, 5)) : null,
                'received_at' => rand(0, 1) ? now() : null,
            ]);
        }
    }
}
