<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            if (! $this->indexExists('managed_files', 'managed_files_original_name_fulltext')) {
                DB::statement('ALTER TABLE managed_files ADD FULLTEXT INDEX managed_files_original_name_fulltext (original_name)');
            }
        }

        if (! $this->indexExists('managed_files', 'managed_files_storage_driver_index')) {
            DB::statement('ALTER TABLE managed_files ADD INDEX managed_files_storage_driver_index (storage_driver)');
        }
    }

    public function down(): void
    {
        if ($this->indexExists('managed_files', 'managed_files_original_name_fulltext')) {
            DB::statement('ALTER TABLE managed_files DROP INDEX managed_files_original_name_fulltext');
        }

        if ($this->indexExists('managed_files', 'managed_files_storage_driver_index')) {
            DB::statement('ALTER TABLE managed_files DROP INDEX managed_files_storage_driver_index');
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            $rows = DB::select(
                'SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
                [$table, $index]
            );
        } catch (\Throwable) {
            return false;
        }

        return ! empty($rows);
    }
};
