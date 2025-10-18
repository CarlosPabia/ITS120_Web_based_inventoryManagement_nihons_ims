<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('action_type', ['Add', 'Deduct'])->after('order_type')->nullable();
        });

        DB::statement("UPDATE orders SET action_type = CASE WHEN order_type = 'Supplier' THEN 'Add' ELSE 'Deduct' END");
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('action_type', ['Add', 'Deduct'])->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('action_type');
        });
    }
};
