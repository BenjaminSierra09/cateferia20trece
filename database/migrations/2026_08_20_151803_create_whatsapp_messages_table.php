<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider_message_id')->nullable()->unique();
            $table->string('direction', 16)->index();
            $table->string('type', 32)->default('text');
            $table->text('body')->nullable();
            $table->string('status', 16)->index();
            $table->string('error_code')->nullable();
            $table->timestamp('sent_at')->index();
            $table->timestamps();

            $table->index(['whatsapp_conversation_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
