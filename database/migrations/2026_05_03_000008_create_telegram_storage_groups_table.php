<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_storage_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telegram_bot_token_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('chat_id', 128);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'title']);
            $table->unique(['user_id', 'telegram_bot_token_id', 'chat_id'], 'telegram_groups_user_bot_chat_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_storage_groups');
    }
};
