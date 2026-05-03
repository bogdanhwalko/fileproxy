<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_storage_groups', function (Blueprint $table) {
            $table->boolean('is_global_default')->default(false)->after('is_default')->index();
        });
    }

    public function down(): void
    {
        Schema::table('telegram_storage_groups', function (Blueprint $table) {
            $table->dropColumn('is_global_default');
        });
    }
};
