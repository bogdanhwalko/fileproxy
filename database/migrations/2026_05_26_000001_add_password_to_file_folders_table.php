<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_folders', function (Blueprint $table) {
            $table->string('password_hash', 255)->nullable()->after('color');
            $table->timestamp('password_set_at')->nullable()->after('password_hash');
        });
    }

    public function down(): void
    {
        Schema::table('file_folders', function (Blueprint $table) {
            $table->dropColumn(['password_hash', 'password_set_at']);
        });
    }
};
