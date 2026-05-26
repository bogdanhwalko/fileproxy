<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16); // idea | bug | question | other
            $table->string('subject', 200);
            $table->text('message');
            $table->string('contact', 200)->nullable(); // optional alt contact (email/telegram)
            $table->string('status', 16)->default('new'); // new | read | resolved
            $table->text('admin_notes')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_messages');
    }
};
