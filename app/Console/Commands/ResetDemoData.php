<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ResetDemoData extends Command
{
    protected $signature = 'app:demo-reset {--force : Run without interactive confirmation}';
    protected $description = 'Delete domain data (except users/RBAC) and reseed demo data with relationships.';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('This will DELETE orders, items, stock, suppliers, activity logs and reseed demo data. Continue?')) {
            $this->warn('Aborted.');
            return self::SUCCESS;
        }

        $tables = [
            'order_items',
            'orders',
            'stock_levels',
            'supplier_catalog',
            'inventory_items',
            'suppliers',
            'activity_log',
        ];

        DB::beginTransaction();
        try {
            // Disable FKs (MySQL)
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('Truncate failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Seed demo data
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\DemoDataSeeder', '--force' => true]);
        $this->info('Demo data reset complete.');
        return self::SUCCESS;
    }
}

