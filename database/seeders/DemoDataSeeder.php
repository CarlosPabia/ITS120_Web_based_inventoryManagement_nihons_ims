<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Supplier;
use App\Models\InventoryItem;
use App\Models\StockLevel;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ActivityLog;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $userId = User::query()->value('id') ?? 1;

        // --- Suppliers ---
        $suppliersData = [
            [
                'supplier_name' => 'Nihon Cafe HQ',
                'contact_person' => 'Central Purchasing',
                'phone' => '+81-3-0000-0000',
                'email' => 'procurement@nihoncafe.jp',
                'address' => '1-1 Shibuya, Tokyo',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'supplier_name' => 'Kyoto Imports',
                'contact_person' => 'Aiko Tanaka',
                'phone' => '+81-75-123-4567',
                'email' => 'sales@kyotoimports.jp',
                'address' => 'Nakagyo Ward, Kyoto',
                'is_active' => true,
            ],
            [
                'supplier_name' => 'BakeHouse Corp.',
                'contact_person' => 'Marc Leblanc',
                'phone' => '+33-1-555-0100',
                'email' => 'orders@bakehouse.co',
                'address' => 'Lyon, France',
                'is_active' => true,
            ],
            [
                'supplier_name' => 'DairyPure Co.',
                'contact_person' => 'John Miller',
                'phone' => '+1-555-0199',
                'email' => 'sales@dairypure.com',
                'address' => 'Madison, WI',
                'is_active' => true,
            ],
        ];

        $suppliers = [];
        foreach ($suppliersData as $data) {
            $supplier = Supplier::create(collect($data)->except('is_system')->toArray());
            if (!empty($data['is_system'])) {
                $supplier->is_system = true; // not fillable
                $supplier->save();
            }
            $suppliers[$supplier->supplier_name] = $supplier;
        }

        // --- Inventory Items ---
        $itemsData = [
            ['name' => 'Matcha Powder', 'unit' => 'kg', 'supplier' => 'Kyoto Imports'],
            ['name' => 'Fresh Milk', 'unit' => 'L', 'supplier' => 'DairyPure Co.'],
            ['name' => 'Sandwich Bread', 'unit' => 'loaves', 'supplier' => 'BakeHouse Corp.'],
            ['name' => 'Vanilla Extract', 'unit' => 'bottle', 'supplier' => 'Nihon Cafe HQ'],
            ['name' => 'Green Tea Leaves', 'unit' => 'kg', 'supplier' => 'Kyoto Imports'],
            ['name' => 'Whipped Cream', 'unit' => 'bottle', 'supplier' => 'DairyPure Co.'],
        ];

        $items = [];
        foreach ($itemsData as $row) {
            $supplier = $suppliers[$row['supplier']];
            $item = InventoryItem::create([
                'item_name' => $row['name'],
                'item_description' => null,
                'supplier_id' => $supplier->id,
                'unit_of_measure' => $row['unit'],
            ]);
            $items[$row['name']] = $item;
            // Link in supplier_catalog as primary
            DB::table('supplier_catalog')->insert([
                'supplier_id' => $supplier->id,
                'inventory_item_id' => $item->id,
                'is_primary' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // --- Stock Batches (with expiries) ---
        $batchPlan = [
            ['item' => 'Matcha Powder', 'qty' => 12, 'expiry' => $now->copy()->addMonths(8)],
            ['item' => 'Matcha Powder', 'qty' => 8, 'expiry' => $now->copy()->addMonths(14)],
            ['item' => 'Fresh Milk', 'qty' => 25, 'expiry' => $now->copy()->addDays(7)],
            ['item' => 'Whipped Cream', 'qty' => 10, 'expiry' => $now->copy()->addDays(20)],
            ['item' => 'Sandwich Bread', 'qty' => 30, 'expiry' => $now->copy()->addDays(5)],
            ['item' => 'Vanilla Extract', 'qty' => 6, 'expiry' => $now->copy()->addMonths(24)],
            ['item' => 'Green Tea Leaves', 'qty' => 18, 'expiry' => $now->copy()->addMonths(10)],
        ];

        foreach ($batchPlan as $bp) {
            $item = $items[$bp['item']];
            StockLevel::create([
                'item_id' => $item->id,
                'quantity' => $bp['qty'],
                'expiry_date' => $bp['expiry']->toDateString(),
                'minimum_stock_threshold' => 10,
            ]);
        }

        // --- Sample Orders (pending only for demo) ---
        $order1 = Order::create([
            'order_type' => 'Supplier',
            'action_type' => 'Add',
            'supplier_id' => $suppliers['Kyoto Imports']->id,
            'order_status' => 'Pending',
            'order_date' => $now->copy()->subDays(2),
            'expected_date' => $now->copy()->addDays(5),
            'created_by_user_id' => $userId,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'item_id' => $items['Matcha Powder']->id,
            'quantity_ordered' => 5,
            'unit_price' => 22.50,
            'expected_stock_expiry' => $now->copy()->addMonths(12)->toDateString(),
        ]);

        $order2 = Order::create([
            'order_type' => 'Customer',
            'action_type' => 'Deduct',
            'supplier_id' => $suppliers['DairyPure Co.']->id,
            'order_status' => 'Pending',
            'order_date' => $now->copy()->subDay(),
            'expected_date' => $now->copy()->subDay(),
            'created_by_user_id' => $userId,
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'item_id' => $items['Fresh Milk']->id,
            'quantity_ordered' => 3,
            'unit_price' => 1.80,
            'expected_stock_expiry' => null,
        ]);

        // --- Activity log ---
        ActivityLog::create([
            'user_id' => $userId,
            'activity_type' => 'Demo Reset',
            'details' => 'Domain data reset and demo sample records seeded.',
            'timestamp' => Carbon::now(),
        ]);
    }
}

