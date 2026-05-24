<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managed_file_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('managed_file_id')->constrained('managed_files')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->foreignId('telegram_storage_group_id')->nullable()->constrained('telegram_storage_groups')->nullOnDelete();
            $table->foreignId('telegram_bot_token_id')->nullable()->constrained('telegram_bot_tokens')->nullOnDelete();
            $table->string('telegram_chat_id', 128)->nullable();
            $table->unsignedBigInteger('telegram_message_id')->nullable();
            $table->string('telegram_file_id')->nullable();
            $table->string('telegram_file_unique_id')->nullable();
            $table->binary('iv')->nullable();           // AES-GCM IV, 12 bytes
            $table->binary('auth_tag')->nullable();     // AES-GCM auth tag, 16 bytes
            $table->unsignedInteger('encrypted_size')->default(0);
            $table->unsignedInteger('plaintext_size')->default(0);
            $table->timestamps();

            $table->unique(['managed_file_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_file_chunks');
    }
};
