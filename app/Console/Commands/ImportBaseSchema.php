<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

#[AsCommand(name: 'db:import-base')]
class ImportBaseSchema extends Command
{
    protected $signature = 'db:import-base {--file=database.sql : Path to the base SQL file relative to project root}';

    protected $description = 'Import the base SQL schema (idempotent; skips if tables already exist)';

    public function handle(): int
    {
        // If core tables exist, assume base schema already present
        if (Schema::hasTable('suppliers') && Schema::hasTable('inventory_items')) {
            $this->info('Base schema already detected; skipping import.');
            return self::SUCCESS;
        }

        $relative = $this->option('file') ?? 'database.sql';
        $path = base_path($relative);

        if (!File::exists($path)) {
            $this->error("SQL file not found: {$relative}");
            return self::FAILURE;
        }

        $sql = File::get($path);
        if (!is_string($sql) || trim($sql) === '') {
            $this->error('SQL file is empty.');
            return self::FAILURE;
        }

        $this->warn("Importing base schema from {$relative} ...");
        try {
            DB::unprepared($sql);
        } catch (\Throwable $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Base schema imported successfully.');
        return self::SUCCESS;
    }
}
