<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        OrderItem::truncate();
        Order::truncate();
        StockLevel::truncate();
        DB::table('supplier_catalog')->truncate();
        InventoryItem::truncate();
        ActivityLog::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $userId = User::query()->orderBy('id')->value('id') ?? 1;

        $supplierDefinitions = [
            [
                'supplier_name' => 'Nihon Cafe HQ',
                'contact_person' => 'Central Purchasing',
                'phone' => '+63 2 8123 4567',
                'email' => 'hq@nihoncafe.ph',
                'address' => 'Bonifacio Global City, Taguig',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'supplier_name' => 'Manila Bean Roasters',
                'contact_person' => 'Leo Santos',
                'phone' => '+63 917 555 8821',
                'email' => 'orders@manilabeanroasters.ph',
                'address' => 'Chino Roces Avenue, Makati City',
                'is_active' => true,
            ],
            [
                'supplier_name' => 'Cebu Pastry Supply',
                'contact_person' => 'Isla Ramirez',
                'phone' => '+63 32 265 4401',
                'email' => 'hello@cebupastry.ph',
                'address' => 'Mandaue City, Cebu',
                'is_active' => true,
            ],
            [
                'supplier_name' => 'Laguna Dairy Cooperative',
                'contact_person' => 'Rica Velasco',
                'phone' => '+63 49 512 7788',
                'email' => 'sales@lagunadairy.ph',
                'address' => 'Calamba, Laguna',
                'is_active' => true,
            ],
        ];

        $suppliers = [];
        foreach ($supplierDefinitions as $definition) {
            $supplier = Supplier::updateOrCreate(
                ['supplier_name' => $definition['supplier_name']],
                collect($definition)->except('is_system')->toArray()
            );

            if (array_key_exists('is_system', $definition)) {
                $supplier->is_system = $definition['is_system'];
                $supplier->save();
            }

            $suppliers[$supplier->supplier_name] = $supplier;
        }

        $finishedGoods = [
            [
                'name' => 'Spanish Latte (Iced)',
                'description' => 'Muscovado-sweetened espresso poured over chilled milk.',
                'unit' => 'cup',
                'default_price' => 185.00,
                'minimum_threshold' => 30,
                'stock' => [
                    ['quantity' => 74, 'expiry' => $now->copy()->addDays(2)],
                ],
            ],
            [
                'name' => 'Matcha Cold Brew Bottle',
                'description' => 'Slow-steeped matcha with calamansi zest in a reusable bottle.',
                'unit' => 'bottle',
                'default_price' => 220.00,
                'minimum_threshold' => 25,
                'stock' => [
                    ['quantity' => 56, 'expiry' => $now->copy()->addDays(10)],
                ],
            ],
            [
                'name' => 'Classic Glazed Donut',
                'description' => 'Yeast-raised donut finished with vanilla bean glaze.',
                'unit' => 'piece',
                'default_price' => 55.00,
                'minimum_threshold' => 80,
                'stock' => [
                    ['quantity' => 150, 'expiry' => $now->copy()->addDays(3)],
                ],
            ],
            [
                'name' => 'Ube Cheese Ensaymada',
                'description' => 'Fluffy ensaymada topped with queso de bola and ube butter.',
                'unit' => 'piece',
                'default_price' => 85.00,
                'minimum_threshold' => 50,
                'stock' => [
                    ['quantity' => 92, 'expiry' => $now->copy()->addDays(4)],
                ],
            ],
            [
                'name' => 'Dark Chocolate Croissant',
                'description' => 'Laminated pastry filled with tablea dark chocolate ganache.',
                'unit' => 'piece',
                'default_price' => 98.00,
                'minimum_threshold' => 40,
                'stock' => [
                    ['quantity' => 66, 'expiry' => $now->copy()->addDays(3)],
                ],
            ],
            [
                'name' => 'Tablea Mocha Frappe',
                'description' => 'Frozen mocha blended with Batangas tablea and cream.',
                'unit' => 'cup',
                'default_price' => 210.00,
                'minimum_threshold' => 35,
                'stock' => [
                    ['quantity' => 80, 'expiry' => null],
                ],
            ],
            [
                'name' => 'Bottled Drip Coffee',
                'description' => 'House blend drip coffee sealed fresh every morning.',
                'unit' => 'bottle',
                'default_price' => 165.00,
                'minimum_threshold' => 20,
                'stock' => [
                    ['quantity' => 44, 'expiry' => $now->copy()->addDays(12)],
                ],
            ],
            [
                'name' => 'Caramel Macchiato (Tall)',
                'description' => 'Espresso layered with vanilla milk and palm sugar caramel.',
                'unit' => 'cup',
                'default_price' => 195.00,
                'minimum_threshold' => 50,
                'stock' => [
                    ['quantity' => 68, 'expiry' => null],
                ],
            ],
            [
                'name' => 'Iced Americano (Tall)',
                'description' => 'Double espresso over ice with a hint of calamansi.',
                'unit' => 'cup',
                'default_price' => 140.00,
                'minimum_threshold' => 50,
                'stock' => [
                    ['quantity' => 122, 'expiry' => null],
                ],
            ],
            [
                'name' => 'Cheese Pandesal Box',
                'description' => 'Half-dozen buttery pandesal stuffed with Laguna kesong puti.',
                'unit' => 'box',
                'default_price' => 120.00,
                'minimum_threshold' => 25,
                'stock' => [
                    ['quantity' => 36, 'expiry' => $now->copy()->addDays(5)],
                ],
            ],
        ];

        $rawMaterials = [
            [
                'name' => 'Whole Arabica Beans (10kg Sack)',
                'description' => 'Single-origin Benguet arabica beans for espresso and drip.',
                'unit' => 'bag',
                'supplier' => 'Manila Bean Roasters',
                'default_price' => 2850.00,
                'minimum_threshold' => 10,
                'stock' => [
                    ['quantity' => 18, 'expiry' => null],
                ],
            ],
            [
                'name' => 'Cold Brew Concentrate Base (5L)',
                'description' => 'Concentrated coffee base for bottled cold brew production.',
                'unit' => 'carboy',
                'supplier' => 'Manila Bean Roasters',
                'default_price' => 2350.00,
                'minimum_threshold' => 6,
                'stock' => [
                    ['quantity' => 9, 'expiry' => $now->copy()->addDays(18)],
                ],
            ],
            [
                'name' => 'Fresh Cow\'s Milk (10L)',
                'description' => 'Pasteurised whole milk sourced from Laguna dairy farms.',
                'unit' => 'tote',
                'supplier' => 'Laguna Dairy Cooperative',
                'default_price' => 1150.00,
                'minimum_threshold' => 12,
                'stock' => [
                    ['quantity' => 20, 'expiry' => $now->copy()->addDays(6)],
                ],
            ],
            [
                'name' => 'Tablea Chocolate Discs (1kg)',
                'description' => 'Batangas-grown cacao discs for mocha and pastry production.',
                'unit' => 'kg',
                'supplier' => 'Manila Bean Roasters',
                'default_price' => 780.00,
                'minimum_threshold' => 15,
                'stock' => [
                    ['quantity' => 22, 'expiry' => $now->copy()->addMonths(8)],
                ],
            ],
            [
                'name' => 'Salted Butter Sheets (5kg)',
                'description' => 'Frozen European-style butter sheets for laminated dough.',
                'unit' => 'case',
                'supplier' => 'Cebu Pastry Supply',
                'default_price' => 2440.00,
                'minimum_threshold' => 8,
                'stock' => [
                    ['quantity' => 12, 'expiry' => $now->copy()->addMonths(2)],
                ],
            ],
            [
                'name' => 'Ube Halaya Spread (3kg Tub)',
                'description' => 'Slow-cooked purple yam jam sweetened with muscovado.',
                'unit' => 'tub',
                'supplier' => 'Cebu Pastry Supply',
                'default_price' => 1680.00,
                'minimum_threshold' => 10,
                'stock' => [
                    ['quantity' => 14, 'expiry' => $now->copy()->addWeeks(6)],
                ],
            ],
        ];

        $catalogItems = array_merge(
            array_map(function (array $item) {
                $item['supplier'] = 'Nihon Cafe HQ';
                return $item;
            }, $finishedGoods),
            $rawMaterials
        );

        $items = [];
        foreach ($catalogItems as $itemDef) {
            $supplier = $suppliers[$itemDef['supplier']] ?? null;
            if (!$supplier) {
                continue;
            }

            $inventoryItem = InventoryItem::create([
                'item_name' => $itemDef['name'],
                'item_description' => $itemDef['description'],
                'supplier_id' => $supplier->id,
                'unit_of_measure' => $itemDef['unit'],
                'default_unit_price' => $itemDef['default_price'],
            ]);

            $items[$itemDef['name']] = $inventoryItem;

            DB::table('supplier_catalog')->insert([
                'supplier_id' => $supplier->id,
                'inventory_item_id' => $inventoryItem->id,
                'is_primary' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($itemDef['stock'] as $stockRow) {
                StockLevel::create([
                    'item_id' => $inventoryItem->id,
                    'quantity' => $stockRow['quantity'],
                    'expiry_date' => $stockRow['expiry'] ? $stockRow['expiry']->toDateString() : null,
                    'minimum_stock_threshold' => $stockRow['minimum_threshold'] ?? $itemDef['minimum_threshold'],
                ]);
            }
        }

        $orderPlans = [
            [
                'order_type' => 'Supplier',
                'action_type' => 'Add',
                'supplier' => 'Cebu Pastry Supply',
                'status' => 'Confirmed',
                'order_date' => $now->copy()->subDays(8)->setTime(8, 30),
                'expected_date' => $now->copy()->subDays(6),
                'processed_at' => $now->copy()->subDays(6)->setTime(9, 15),
                'items' => [
                    ['item' => 'Salted Butter Sheets (5kg)', 'quantity' => 18, 'price' => 2400.00, 'expiry' => $now->copy()->addMonths(2)],
                    ['item' => 'Ube Halaya Spread (3kg Tub)', 'quantity' => 24, 'price' => 1650.00, 'expiry' => $now->copy()->addWeeks(8)],
                ],
            ],
            [
                'order_type' => 'Supplier',
                'action_type' => 'Add',
                'supplier' => 'Manila Bean Roasters',
                'status' => 'Confirmed',
                'order_date' => $now->copy()->subDays(7)->setTime(9, 45),
                'expected_date' => $now->copy()->subDays(5),
                'processed_at' => $now->copy()->subDays(5)->setTime(11, 20),
                'items' => [
                    ['item' => 'Whole Arabica Beans (10kg Sack)', 'quantity' => 20, 'price' => 2800.00, 'expiry' => null],
                    ['item' => 'Cold Brew Concentrate Base (5L)', 'quantity' => 12, 'price' => 2325.00, 'expiry' => $now->copy()->addDays(20)],
                    ['item' => 'Tablea Chocolate Discs (1kg)', 'quantity' => 25, 'price' => 765.00, 'expiry' => $now->copy()->addMonths(9)],
                ],
            ],
            [
                'order_type' => 'Supplier',
                'action_type' => 'Add',
                'supplier' => 'Laguna Dairy Cooperative',
                'status' => 'Confirmed',
                'order_date' => $now->copy()->subDays(6)->setTime(7, 10),
                'expected_date' => $now->copy()->subDays(4),
                'processed_at' => $now->copy()->subDays(4)->setTime(8, 55),
                'items' => [
                    ['item' => 'Fresh Cow\'s Milk (10L)', 'quantity' => 28, 'price' => 1125.00, 'expiry' => $now->copy()->addDays(7)],
                ],
            ],
            [
                'order_type' => 'Supplier',
                'action_type' => 'Add',
                'supplier' => 'Nihon Cafe HQ',
                'status' => 'Confirmed',
                'order_date' => $now->copy()->subDays(5)->setTime(7, 55),
                'expected_date' => $now->copy()->subDays(4),
                'processed_at' => $now->copy()->subDays(4)->setTime(10, 5),
                'items' => [
                    ['item' => 'Spanish Latte (Iced)', 'quantity' => 140, 'price' => 105.00, 'expiry' => $now->copy()->addDays(3)],
                    ['item' => 'Matcha Cold Brew Bottle', 'quantity' => 90, 'price' => 130.00, 'expiry' => $now->copy()->addDays(15)],
                    ['item' => 'Classic Glazed Donut', 'quantity' => 220, 'price' => 32.00, 'expiry' => $now->copy()->addDays(4)],
                    ['item' => 'Ube Cheese Ensaymada', 'quantity' => 140, 'price' => 58.00, 'expiry' => $now->copy()->addDays(5)],
                    ['item' => 'Dark Chocolate Croissant', 'quantity' => 110, 'price' => 68.00, 'expiry' => $now->copy()->addDays(5)],
                    ['item' => 'Caramel Macchiato (Tall)', 'quantity' => 120, 'price' => 115.00, 'expiry' => null],
                    ['item' => 'Tablea Mocha Frappe', 'quantity' => 90, 'price' => 135.00, 'expiry' => null],
                ],
            ],
            [
                'order_type' => 'Supplier',
                'action_type' => 'Add',
                'supplier' => 'Laguna Dairy Cooperative',
                'status' => 'Pending',
                'order_date' => $now->copy()->subDay()->setTime(8, 0),
                'expected_date' => $now->copy()->addDays(2),
                'processed_at' => null,
                'items' => [
                    ['item' => 'Fresh Cow\'s Milk (10L)', 'quantity' => 18, 'price' => 1125.00, 'expiry' => $now->copy()->addDays(8)],
                ],
            ],
            [
                'order_type' => 'Customer',
                'action_type' => 'Deduct',
                'supplier' => 'Nihon Cafe HQ',
                'status' => 'Confirmed',
                'order_date' => $now->copy()->subDays(3)->setTime(16, 40),
                'expected_date' => $now->copy()->subDays(3),
                'processed_at' => $now->copy()->subDays(2)->setTime(18, 10),
                'items' => [
                    ['item' => 'Spanish Latte (Iced)', 'quantity' => 58, 'price' => 185.00, 'expiry' => null],
                    ['item' => 'Caramel Macchiato (Tall)', 'quantity' => 42, 'price' => 195.00, 'expiry' => null],
                ],
            ],
            [
                'order_type' => 'Customer',
                'action_type' => 'Deduct',
                'supplier' => 'Nihon Cafe HQ',
                'status' => 'Confirmed',
                'order_date' => $now->copy()->subDays(2)->setTime(14, 15),
                'expected_date' => $now->copy()->subDays(2),
                'processed_at' => $now->copy()->subDay()->setTime(17, 20),
                'items' => [
                    ['item' => 'Classic Glazed Donut', 'quantity' => 80, 'price' => 55.00, 'expiry' => null],
                    ['item' => 'Ube Cheese Ensaymada', 'quantity' => 45, 'price' => 85.00, 'expiry' => null],
                    ['item' => 'Dark Chocolate Croissant', 'quantity' => 32, 'price' => 98.00, 'expiry' => null],
                    ['item' => 'Cheese Pandesal Box', 'quantity' => 20, 'price' => 120.00, 'expiry' => null],
                ],
            ],
            [
                'order_type' => 'Customer',
                'action_type' => 'Deduct',
                'supplier' => 'Nihon Cafe HQ',
                'status' => 'Confirmed',
                'order_date' => $now->copy()->subDay()->setTime(10, 25),
                'expected_date' => $now->copy()->subDay(),
                'processed_at' => $now->copy()->subHours(12),
                'items' => [
                    ['item' => 'Matcha Cold Brew Bottle', 'quantity' => 30, 'price' => 220.00, 'expiry' => null],
                    ['item' => 'Bottled Drip Coffee', 'quantity' => 20, 'price' => 165.00, 'expiry' => null],
                    ['item' => 'Tablea Mocha Frappe', 'quantity' => 25, 'price' => 210.00, 'expiry' => null],
                    ['item' => 'Iced Americano (Tall)', 'quantity' => 40, 'price' => 140.00, 'expiry' => null],
                    ['item' => 'Caramel Macchiato (Tall)', 'quantity' => 35, 'price' => 195.00, 'expiry' => null],
                ],
            ],
            [
                'order_type' => 'Customer',
                'action_type' => 'Deduct',
                'supplier' => 'Nihon Cafe HQ',
                'status' => 'Cancelled',
                'order_date' => $now->copy()->subHours(6),
                'expected_date' => $now->copy()->subHours(6),
                'processed_at' => null,
                'items' => [
                    ['item' => 'Matcha Cold Brew Bottle', 'quantity' => 12, 'price' => 220.00, 'expiry' => null],
                ],
            ],
        ];

        foreach ($orderPlans as $plan) {
            $supplier = $suppliers[$plan['supplier']] ?? null;
            if (!$supplier) {
                continue;
            }

            $order = Order::create([
                'order_type' => $plan['order_type'],
                'action_type' => $plan['action_type'],
                'supplier_id' => $supplier->id,
                'order_status' => $plan['status'],
                'order_date' => $plan['order_date']->toDateTimeString(),
                'expected_date' => $plan['expected_date'] ? $plan['expected_date']->toDateString() : null,
                'status_processed_at' => $plan['status'] === 'Confirmed' && $plan['processed_at']
                    ? $plan['processed_at']->toDateTimeString()
                    : null,
                'created_by_user_id' => $userId,
            ]);

            foreach ($plan['items'] as $orderItem) {
                $inventoryItem = $items[$orderItem['item']] ?? null;
                if (!$inventoryItem) {
                    continue;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $inventoryItem->id,
                    'quantity_ordered' => $orderItem['quantity'],
                    'unit_price' => $orderItem['price'],
                    'expected_stock_expiry' => $orderItem['expiry'] ? $orderItem['expiry']->toDateString() : null,
                ]);
            }
        }

        ActivityLog::insert([
            [
                'user_id' => $userId,
                'activity_type' => 'Dataset Reset',
                'details' => 'Cleared transactional tables while preserving users, RBAC, and supplier records.',
                'timestamp' => $now->copy()->subMinutes(45),
            ],
            [
                'user_id' => $userId,
                'activity_type' => 'Input Materials Restock',
                'details' => 'Confirmed raw material deliveries from Cebu Pastry Supply, Manila Bean Roasters, and Laguna Dairy Cooperative.',
                'timestamp' => $now->copy()->subMinutes(20),
            ],
            [
                'user_id' => $userId,
                'activity_type' => 'HQ Deliveries & Sales',
                'details' => 'Recorded HQ finished goods transfer plus confirmed Spanish Latte and pastry sales.',
                'timestamp' => $now->copy()->subMinutes(5),
            ],
        ]);
    }
}
