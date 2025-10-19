<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE suppliers MODIFY contact_person TEXT NULL');
        DB::statement('ALTER TABLE suppliers MODIFY email TEXT NULL');
        DB::statement('ALTER TABLE suppliers MODIFY phone TEXT NULL');
        DB::statement('ALTER TABLE suppliers MODIFY address TEXT NULL');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE suppliers MODIFY contact_person VARCHAR(100) NULL");
        DB::statement("ALTER TABLE suppliers MODIFY email VARCHAR(100) NULL");
        DB::statement("ALTER TABLE suppliers MODIFY phone VARCHAR(20) NULL");
        DB::statement("ALTER TABLE suppliers MODIFY address VARCHAR(255) NULL");
    }
};
