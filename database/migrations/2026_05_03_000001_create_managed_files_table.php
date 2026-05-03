<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managed_files', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('stored_name')->unique();
            $table->string('path')->unique();
            $table->string('mime_type')->nullable();
            $table->string('extension', 32)->nullable()->index();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_files');
    }
};
