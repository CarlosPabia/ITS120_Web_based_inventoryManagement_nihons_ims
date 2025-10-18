<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("UPDATE orders SET order_type='Supplier' WHERE order_type='Add'");
        DB::statement("UPDATE orders SET order_type='Customer' WHERE order_type='Deduct'");

        DB::statement("ALTER TABLE orders MODIFY order_type ENUM('Supplier','Customer') NOT NULL" );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY order_type ENUM('Add','Deduct') NOT NULL" );
        DB::statement("UPDATE orders SET order_type='Add' WHERE order_type='Supplier'");
        DB::statement("UPDATE orders SET order_type='Deduct' WHERE order_type='Customer'");
    }
};
