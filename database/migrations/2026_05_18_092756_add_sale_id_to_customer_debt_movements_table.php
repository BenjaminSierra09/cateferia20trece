<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_debt_movements', function (Blueprint $table) {
            $table->foreignId('sale_id')
                ->nullable()
                ->after('customer_id')
                ->constrained()
                ->nullOnDelete();
        });

        DB::table('customer_debt_movements')
            ->select(['id', 'notes'])
            ->whereNull('sale_id')
            ->where('notes', 'like', 'Cargo automático por venta #%')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $movement): void {
                if (! preg_match('/#(\d+)/', (string) $movement->notes, $matches)) {
                    return;
                }

                DB::table('customer_debt_movements')
                    ->whereKey($movement->id)
                    ->update([
                        'sale_id' => (int) $matches[1],
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_debt_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
        });
    }
};
