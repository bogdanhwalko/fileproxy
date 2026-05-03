<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('managed_files', function (Blueprint $table) {
            $table
                ->foreignId('folder_id')
                ->nullable()
                ->after('user_id')
                ->constrained('file_folders')
                ->nullOnDelete();

            $table->index(['user_id', 'folder_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('managed_files', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'folder_id', 'created_at']);
            $table->dropConstrainedForeignId('folder_id');
        });
    }
};
