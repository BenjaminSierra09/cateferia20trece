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
        Schema::create('mercado_pago_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mercado_pago_point_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_id')->nullable()->index();
            $table->string('topic')->nullable()->index();
            $table->string('type')->nullable()->index();
            $table->string('action')->nullable();
            $table->string('resource_id')->nullable()->index();
            $table->string('external_reference')->nullable()->index();
            $table->string('mercado_pago_order_id')->nullable()->index();
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercado_pago_webhook_events');
    }
};
