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
        Schema::create('whatsapp_campaign_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('whatsapp_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('whatsapp_message_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('phone', 32);
            $table->string('name');
            $table->text('message_preview');
            $table->json('parameters')->nullable();
            $table->timestamp('consented_at');
            $table->string('consent_source', 64)->nullable();
            $table->string('status', 20)->index();
            $table->string('provider_message_id')->nullable()->unique();
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['whatsapp_campaign_id', 'phone']);
            $table->index(['whatsapp_campaign_id', 'status', 'id'], 'whatsapp_campaign_recipient_queue_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_campaign_recipients');
    }
};
