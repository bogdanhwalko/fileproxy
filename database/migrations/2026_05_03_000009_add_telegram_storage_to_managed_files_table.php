<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('managed_files', function (Blueprint $table) {
            $table->string('storage_driver', 32)->default('local')->after('folder_id')->index();
            $table->foreignId('telegram_bot_token_id')->nullable()->after('storage_driver')->constrained()->nullOnDelete();
            $table->foreignId('telegram_storage_group_id')->nullable()->after('telegram_bot_token_id')->constrained()->nullOnDelete();
            $table->string('telegram_chat_id', 128)->nullable()->after('telegram_storage_group_id');
            $table->unsignedBigInteger('telegram_message_id')->nullable()->after('telegram_chat_id');
            $table->text('telegram_file_id')->nullable()->after('telegram_message_id');
            $table->string('telegram_file_unique_id')->nullable()->after('telegram_file_id');
            $table->json('telegram_response')->nullable()->after('telegram_file_unique_id');

            $table->index(['user_id', 'storage_driver', 'created_at'], 'managed_files_user_storage_created_index');
            $table->index(['telegram_chat_id', 'telegram_message_id'], 'managed_files_telegram_message_index');
        });
    }

    public function down(): void
    {
        Schema::table('managed_files', function (Blueprint $table) {
            $table->dropIndex('managed_files_user_storage_created_index');
            $table->dropIndex('managed_files_telegram_message_index');
            $table->dropConstrainedForeignId('telegram_storage_group_id');
            $table->dropConstrainedForeignId('telegram_bot_token_id');
            $table->dropColumn([
                'storage_driver',
                'telegram_chat_id',
                'telegram_message_id',
                'telegram_file_id',
                'telegram_file_unique_id',
                'telegram_response',
            ]);
        });
    }
};
