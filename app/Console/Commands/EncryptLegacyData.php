<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

class EncryptLegacyData extends Command
{
    protected $signature = 'data:encrypt-legacy';

    protected $description = 'Encrypt legacy plaintext values for suppliers, inventory items, and activity logs.';

    public function handle(): int
    {
        $this->encryptTable(
            'suppliers',
            ['contact_person', 'email', 'phone', 'address']
        );

        $this->encryptTable(
            'inventory_items',
            ['item_description']
        );

        $this->encryptTable(
            'activity_log',
            ['details']
        );

        $this->info('Legacy data encryption complete.');
        return Command::SUCCESS;
    }

    private function encryptTable(string $table, array $columns): void
    {
        $this->info("Processing table: {$table}");

        DB::table($table)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, $columns) {
                foreach ($rows as $row) {
                    $updates = [];
                    foreach ($columns as $column) {
                        if (!property_exists($row, $column) || $row->{$column} === null) {
                            continue;
                        }

                        $value = $row->{$column};
                        if ($this->isEncrypted($value)) {
                            continue;
                        }

                        try {
                            $updates[$column] = Crypt::encryptString($value);
                        } catch (Throwable $exception) {
                            $this->warn("Failed to encrypt {$table}.{$column} for ID {$row->id}: {$exception->getMessage()}");
                        }
                    }

                    if (!empty($updates)) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }
}
