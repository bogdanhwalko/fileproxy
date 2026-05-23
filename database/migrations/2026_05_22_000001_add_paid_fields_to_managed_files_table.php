<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('managed_files', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('share_expires_at');
            $table->unsignedInteger('price_cents')->nullable()->after('is_paid');
            $table->char('currency', 3)->nullable()->after('price_cents');
            $table->unsignedSmallInteger('purchase_max_downloads')->nullable()->after('currency');
            $table->unsignedSmallInteger('purchase_access_hours')->nullable()->after('purchase_max_downloads');

            $table->index(['is_paid']);
        });
    }

    public function down(): void
    {
        Schema::table('managed_files', function (Blueprint $table) {
            $table->dropIndex(['is_paid']);
            $table->dropColumn([
                'is_paid',
                'price_cents',
                'currency',
                'purchase_max_downloads',
                'purchase_access_hours',
            ]);
        });
    }
};
