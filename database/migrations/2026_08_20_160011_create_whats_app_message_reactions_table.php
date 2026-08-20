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
        Schema::create('whatsapp_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider_message_id')->nullable()->unique();
            $table->string('direction', 16);
            $table->string('emoji', 32);
            $table->string('status', 16);
            $table->string('error_code')->nullable();
            $table->timestamp('reacted_at');
            $table->timestamps();

            $table->unique(['whatsapp_message_id', 'direction']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_reactions');
    }
};
