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
        Schema::table('branches', function (Blueprint $table): void {
            $table->boolean('mercado_pago_is_active')->default(false)->after('is_active');
            $table->text('mercado_pago_access_token')->nullable()->after('mercado_pago_is_active');
            $table->text('mercado_pago_public_key')->nullable()->after('mercado_pago_access_token');
            $table->string('mercado_pago_default_terminal_id')->nullable()->after('mercado_pago_public_key');
            $table->string('mercado_pago_default_terminal_name')->nullable()->after('mercado_pago_default_terminal_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->dropColumn([
                'mercado_pago_is_active',
                'mercado_pago_access_token',
                'mercado_pago_public_key',
                'mercado_pago_default_terminal_id',
                'mercado_pago_default_terminal_name',
            ]);
        });
    }
};
