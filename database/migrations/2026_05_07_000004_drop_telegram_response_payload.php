<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('managed_files', 'telegram_response')) {
            DB::table('managed_files')
                ->whereNotNull('telegram_response')
                ->update(['telegram_response' => null]);
        }
    }

    public function down(): void
    {
        // No-op: payload був суто діагностичний, відновити неможливо.
    }
};
