<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $mainBranch = Branch::where('is_main', true)->first();
        $branches = Branch::where('is_main', false)->get();
        
        // Create admin user for main branch
        User::create([
            'username' => 'admin',
            'email' => 'admin@electronics-store.com',
            'password' => Hash::make('password'),
            'branch_id' => $mainBranch->id,
            'role' => 'admin',
        ]);
        
        // Create stock manager for main branch
        User::create([
            'username' => 'stock_manager',
            'email' => 'stock@electronics-store.com',
            'password' => Hash::make('password'),
            'branch_id' => $mainBranch->id,
            'role' => 'stock_manager',
        ]);
        
        // Create users for other branches
        foreach ($branches as $index => $branch) {
            User::create([
                'username' => 'manager_branch_' . ($index + 2),
                'email' => 'manager.branch' . ($index + 2) . '@electronics-store.com',
                'password' => Hash::make('password'),
                'branch_id' => $branch->id,
                'role' => 'branch_manager',
            ]);
            
            User::create([
                'username' => 'staff_branch_' . ($index + 2),
                'email' => 'staff.branch' . ($index + 2) . '@electronics-store.com',
                'password' => Hash::make('password'),
                'branch_id' => $branch->id,
                'role' => 'order_creator',
            ]);
        }
    }
}

