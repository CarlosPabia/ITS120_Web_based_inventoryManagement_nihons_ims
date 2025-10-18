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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'expected_date')) {
                $table->date('expected_date')->nullable()->after('order_date');
            }

            if (!Schema::hasColumn('orders', 'status_processed_at')) {
                $table->timestamp('status_processed_at')->nullable()->after('order_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'status_processed_at')) {
                $table->dropColumn('status_processed_at');
            }
            if (Schema::hasColumn('orders', 'expected_date')) {
                $table->dropColumn('expected_date');
            }
        });
    }
};
