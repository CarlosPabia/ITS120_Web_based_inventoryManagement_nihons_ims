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
        Schema::table('suppliers', function (Blueprint $table) {
            // Add the new 'address' column. It can be nullable (optional).
            $table->string('address', 255)->nullable()->after('email');

            // Add the new 'is_active' column. It will default to 'true' for all new suppliers.
            $table->boolean('is_active')->default(true)->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // This allows you to undo the migration if needed.
            $table->dropColumn('address');
            $table->dropColumn('is_active');
        });
    }
};
?>
```

After you have created and saved this file, run the migration command in your terminal to apply the changes to your database:

```bash
php artisan migrate
