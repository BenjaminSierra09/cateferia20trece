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
        Schema::table('mercado_pago_point_orders', function (Blueprint $table): void {
            $table->dropForeign(['sale_id']);
            $table->foreignId('sale_id')->nullable()->change();
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mercado_pago_point_orders', function (Blueprint $table): void {
            $table->dropForeign(['sale_id']);
            $table->foreignId('sale_id')->nullable(false)->change();
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
        });
    }
};
