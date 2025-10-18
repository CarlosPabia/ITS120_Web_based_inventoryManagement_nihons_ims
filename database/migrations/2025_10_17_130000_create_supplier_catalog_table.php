<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('supplier_catalog')) {
            Schema::create('supplier_catalog', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->unique(['supplier_id', 'inventory_item_id']);
            });
        }

        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('is_active');
            }
        });

        // Populate catalog links for existing inventory assignments
        $now = Carbon::now();
        $existingItems = DB::table('inventory_items')
            ->select('id', 'supplier_id')
            ->whereNotNull('supplier_id')
            ->get();

        foreach ($existingItems as $item) {
            DB::table('supplier_catalog')->updateOrInsert(
                [
                    'supplier_id' => $item->supplier_id,
                    'inventory_item_id' => $item->id,
                ],
                [
                    'is_primary' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // Ensure a system supplier exists for Nihon Cafe HQ
        $systemSupplierId = DB::table('suppliers')
            ->where('is_system', true)
            ->orWhere('supplier_name', 'Nihon Cafe HQ')
            ->value('id');

        if (!$systemSupplierId) {
            $systemSupplierId = DB::table('suppliers')->insertGetId([
                'supplier_name' => 'Nihon Cafe HQ',
                'contact_person' => 'Central Purchasing',
                'phone' => '+81-3-0000-0000',
                'email' => 'procurement@nihoncafe.jp',
                'address' => '1-1 Shibuya, Tokyo',
                'is_active' => true,
                'is_system' => true,
            ]);
        } else {
            DB::table('suppliers')->where('id', $systemSupplierId)->update(['is_system' => true, 'is_active' => true]);
        }

        // Seed core Nihon Cafe catalog items if they do not yet exist
        $coreCatalog = [
            ['item_name' => 'Espresso Roast Beans 1kg', 'unit_of_measure' => 'kg', 'item_description' => 'Signature espresso roast beans.'],
            ['item_name' => 'Cold Brew Concentrate 5L', 'unit_of_measure' => 'L', 'item_description' => 'House cold brew concentrate, ready to dilute.'],
            ['item_name' => 'Glazed Donuts Tray (24 pcs)', 'unit_of_measure' => 'tray', 'item_description' => 'Freshly baked glazed donuts, 24 per tray.'],
            ['item_name' => 'Matcha Latte Mix 1kg', 'unit_of_measure' => 'kg', 'item_description' => 'Sweetened matcha latte mix.'],
            ['item_name' => 'Croissant Dough Pack (40 pcs)', 'unit_of_measure' => 'pack', 'item_description' => 'Ready-to-bake croissant dough, 40 pieces.'],
        ];

        foreach ($coreCatalog as $catalogItem) {
            $existing = DB::table('inventory_items')->where('item_name', $catalogItem['item_name'])->first();

            if ($existing) {
                $itemId = $existing->id;
                DB::table('inventory_items')->where('id', $itemId)->update(['supplier_id' => $systemSupplierId]);
            } else {
                $itemId = DB::table('inventory_items')->insertGetId([
                    'item_name' => $catalogItem['item_name'],
                    'item_description' => $catalogItem['item_description'],
                    'supplier_id' => $systemSupplierId,
                    'unit_of_measure' => $catalogItem['unit_of_measure'],
                ]);

                DB::table('stock_levels')->insert([
                    'item_id' => $itemId,
                    'quantity' => 50,
                    'minimum_stock_threshold' => 10,
                    'last_updated_at' => $now,
                ]);
            }

            DB::table('supplier_catalog')->updateOrInsert(
                [
                    'supplier_id' => $systemSupplierId,
                    'inventory_item_id' => $itemId,
                ],
                [
                    'is_primary' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'is_system')) {
                $table->dropColumn('is_system');
            }
        });

        Schema::dropIfExists('supplier_catalog');
    }
};
