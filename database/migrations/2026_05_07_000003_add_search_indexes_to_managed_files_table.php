<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE managed_files ADD FULLTEXT INDEX managed_files_original_name_fulltext (original_name)');
        }

        Schema::table('managed_files', function (Blueprint $table) {
            $table->index('storage_driver', 'managed_files_storage_driver_index');
        });
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE managed_files DROP INDEX managed_files_original_name_fulltext');
        }

        Schema::table('managed_files', function (Blueprint $table) {
            $table->dropIndex('managed_files_storage_driver_index');
        });
    }
};
