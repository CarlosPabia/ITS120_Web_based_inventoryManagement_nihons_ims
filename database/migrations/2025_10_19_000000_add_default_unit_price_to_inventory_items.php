<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('inventory_items') && !Schema::hasColumn('inventory_items', 'default_unit_price')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->decimal('default_unit_price', 10, 2)->default(0)->after('unit_of_measure');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inventory_items') && Schema::hasColumn('inventory_items', 'default_unit_price')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropColumn('default_unit_price');
            });
        }
    }
};
