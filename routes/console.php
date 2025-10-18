<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Supplier;
use App\Models\InventoryItem;
use App\Models\StockLevel;

// Demo data reset: safely truncate domain tables and reseed relationships
Artisan::command('app:demo-reset {--force}', function () {
    if (!$this->option('force')) {
        if (!$this->confirm('This will DELETE orders, items, stock, suppliers, activity logs and reseed demo data. Continue?')) {
            $this->warn('Aborted.');
            return;
        }
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

    try {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    } catch (\Throwable $e) {
        $this->error('Truncate failed: ' . $e->getMessage());
        return;
    }

    $this->call('db:seed', ['--class' => 'Database\\Seeders\\DemoDataSeeder', '--force' => true]);
    $this->info('Demo data reset complete.');
})->purpose('Reset domain data (keep users/RBAC) and reseed demo data.');

// Purge domain data but keep Suppliers (and all user/RBAC data). No seed.
Artisan::command('app:purge-keep-suppliers {--force}', function () {
    if (!$this->option('force')) {
        if (!$this->confirm('This will DELETE orders, items, stock levels, supplier_catalog, activity logs. Suppliers, users, roles are kept. Continue?')) {
            $this->warn('Aborted.');
            return;
        }
    }

    $tables = [
        'order_items',
        'orders',
        'stock_levels',
        'supplier_catalog',
        'inventory_items',
        'activity_log',
    ];

    try {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    } catch (\Throwable $e) {
        $this->error('Purge failed: ' . $e->getMessage());
        return;
    }

    $this->info('Domain data purged. Suppliers and RBAC preserved.');
})->purpose('Delete all domain data except users/RBAC and suppliers.');

// Seed catalog items for existing suppliers (creates inventory items + supplier_catalog + stock).
Artisan::command('app:seed-catalog {--force}', function () {
    if (!$this->option('force')) {
        if (!$this->confirm('Seed default catalog items for all suppliers?')) {
            $this->warn('Aborted.');
            return;
        }
    }

    $now = Carbon::now();

    // Default catalog sets per known supplier; unknowns get generic items.
    $catalogMap = [
        'Kyoto Imports' => [
            ['item' => 'Matcha Powder', 'unit' => 'kg'],
            ['item' => 'Green Tea Leaves', 'unit' => 'kg'],
            ['item' => 'Sencha Tea Bags (100)', 'unit' => 'box'],
        ],
        'DairyPure Co.' => [
            ['item' => 'Fresh Milk', 'unit' => 'L'],
            ['item' => 'Soy Milk', 'unit' => 'L'],
            ['item' => 'Whipped Cream', 'unit' => 'bottle'],
        ],
        'BakeHouse Corp.' => [
            ['item' => 'Sandwich Bread', 'unit' => 'loaves'],
            ['item' => 'Croissant Dough Pack (40 pcs)', 'unit' => 'pack'],
            ['item' => 'Cheesecake', 'unit' => 'pcs'],
        ],
        'Nihon Cafe HQ' => [
            ['item' => 'Espresso Roast Beans 1kg', 'unit' => 'kg'],
            ['item' => 'Cold Brew Concentrate 5L', 'unit' => 'L'],
            ['item' => 'Vanilla Extract', 'unit' => 'bottle'],
        ],
    ];

    $suppliers = Supplier::orderBy('supplier_name')->get();
    $created = 0; $linked = 0; $stocked = 0;

    foreach ($suppliers as $supplier) {
        $set = $catalogMap[$supplier->supplier_name] ?? [
            ['item' => $supplier->supplier_name . ' Item A', 'unit' => 'pcs'],
            ['item' => $supplier->supplier_name . ' Item B', 'unit' => 'pcs'],
            ['item' => $supplier->supplier_name . ' Item C', 'unit' => 'pcs'],
        ];

        foreach ($set as $cfg) {
            // Find or create inventory item under this supplier
            $item = InventoryItem::where('item_name', $cfg['item'])->first();
            if (!$item) {
                $item = InventoryItem::create([
                    'item_name' => $cfg['item'],
                    'item_description' => null,
                    'supplier_id' => $supplier->id,
                    'unit_of_measure' => $cfg['unit'],
                ]);
                $created++;
            } else {
                // Ensure it’s assigned to this supplier if not already
                if ($item->supplier_id !== $supplier->id) {
                    $item->supplier_id = $supplier->id;
                    $item->save();
                }
            }

            // Link in supplier_catalog
            $exists = DB::table('supplier_catalog')
                ->where('supplier_id', $supplier->id)
                ->where('inventory_item_id', $item->id)
                ->exists();
            if (!$exists) {
                DB::table('supplier_catalog')->insert([
                    'supplier_id' => $supplier->id,
                    'inventory_item_id' => $item->id,
                    'is_primary' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $linked++;
            }

            // Seed a small stock batch so UI has quantities/expiry
            $hasStock = StockLevel::where('item_id', $item->id)->exists();
            if (!$hasStock) {
                $expiry = $now->copy()->addDays(rand(15, 120))->toDateString();
                StockLevel::create([
                    'item_id' => $item->id,
                    'quantity' => rand(5, 30),
                    'expiry_date' => $expiry,
                    'minimum_stock_threshold' => 10,
                ]);
                $stocked++;
            }
        }
    }

    $this->info("Catalog seeding complete: items created={$created}, links={$linked}, stocked={$stocked}.");
})->purpose('Seed catalog items for all suppliers and add initial stock.');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
