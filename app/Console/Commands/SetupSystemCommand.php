<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupSystemCommand extends Command
{
    protected $signature = 'system:setup';
    protected $description = 'Set up the electronics store management system';
    
    public function handle()
    {
        $this->info('Setting up Electronics Store Management System...');

        // Run migrations
        $this->call('migrate:fresh');

        // Seed database
        $this->call('db:seed');

        // Create storage links
        $this->call('storage:link');

        $this->info('System setup completed successfully!');

        $this->table(
            ['Username', 'Email', 'Password', 'Role', 'Branch'],
            [
                ['admin', 'admin@electronics-store.com', 'password', 'admin', 'Main Branch'],
                ['stock_manager', 'stock@electronics-store.com', 'password', 'stock_manager', 'Main Branch'],
                ['manager_branch_2', 'manager.branch2@electronics-store.com', 'password', 'branch_manager', 'Selangor Branch'],
            ]
        );
    }
}
