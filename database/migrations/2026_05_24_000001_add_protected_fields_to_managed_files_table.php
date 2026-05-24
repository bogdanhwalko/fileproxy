<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('managed_files', function (Blueprint $table) {
            $table->boolean('is_protected')->default(false)->after('status')->index();
            $table->text('encryption_key')->nullable()->after('is_protected');   // Laravel 'encrypted' cast
            $table->string('encryption_method', 32)->nullable()->after('encryption_key');
            $table->unsignedSmallInteger('chunk_count')->nullable()->after('encryption_method');
            $table->unsignedBigInteger('original_size')->nullable()->after('chunk_count');
        });
    }

    public function down(): void
    {
        Schema::table('managed_files', function (Blueprint $table) {
            $table->dropIndex(['is_protected']);
            $table->dropColumn([
                'is_protected',
                'encryption_key',
                'encryption_method',
                'chunk_count',
                'original_size',
            ]);
        });
    }
};
