<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('managed_file_id')->constrained('managed_files')->cascadeOnDelete();
            $table->string('access_token', 64)->unique();
            $table->string('status', 16)->default('pending');
            $table->string('buyer_email')->nullable();
            $table->string('lemon_checkout_id')->nullable();
            $table->string('lemon_order_id')->nullable()->unique();
            $table->unsignedInteger('amount_cents')->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedSmallInteger('downloads_count')->default(0);
            $table->unsignedSmallInteger('max_downloads')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['managed_file_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_purchases');
    }
};
