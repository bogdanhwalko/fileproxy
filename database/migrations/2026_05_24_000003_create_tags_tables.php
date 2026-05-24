<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 64);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('managed_file_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('managed_file_id')->constrained('managed_files')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['managed_file_id', 'tag_id']);
            $table->index('tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_file_tag');
        Schema::dropIfExists('tags');
    }
};
