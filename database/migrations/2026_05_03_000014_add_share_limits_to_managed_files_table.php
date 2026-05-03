<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('managed_files', function (Blueprint $table) {
            $table->unsignedInteger('share_max_views')->nullable()->after('share_token');
            $table->unsignedInteger('share_views_count')->default(0)->after('share_max_views');
            $table->dateTime('share_expires_at')->nullable()->after('share_views_count');
        });
    }

    public function down(): void
    {
        Schema::table('managed_files', function (Blueprint $table) {
            $table->dropColumn([
                'share_max_views',
                'share_views_count',
                'share_expires_at',
            ]);
        });
    }
};
