<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run()
    {
        Branch::create([
            'name' => 'Main Branch',
            'location' => 'Kuala Lumpur',
            'is_main' => true,
        ]);
        
        $branches = [
            ['name' => 'Selangor Branch', 'location' => 'Shah Alam'],
            ['name' => 'Penang Branch', 'location' => 'George Town'],
            ['name' => 'Johor Branch', 'location' => 'Johor Bahru'],
            ['name' => 'Sabah Branch', 'location' => 'Kota Kinabalu'],
        ];
        
        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}

