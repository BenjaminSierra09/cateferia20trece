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
        Schema::create('mercado_pago_point_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('terminal_id');
            $table->string('terminal_name')->nullable();
            $table->string('external_reference')->unique();
            $table->string('idempotency_key')->unique();
            $table->string('mercado_pago_order_id')->nullable()->index();
            $table->string('status')->default('created')->index();
            $table->decimal('amount', 10, 2);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('last_webhook_payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercado_pago_point_orders');
    }
};
