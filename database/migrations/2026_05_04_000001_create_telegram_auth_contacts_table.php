<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_auth_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_user_id', 64)->unique();
            $table->string('phone', 32)->index();
            $table->string('first_name')->nullable();
            $table->string('username')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_auth_contacts');
    }
};
