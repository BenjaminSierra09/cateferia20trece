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
        Schema::table('sales', function (Blueprint $table): void {
            $table->string('billing_token', 7)->nullable()->unique()->after('id');
        });

        $usedTokens = [];

        DB::table('sales')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($sales) use (&$usedTokens): void {
                foreach ($sales as $sale) {
                    do {
                        $token = $this->newBillingToken();
                    } while (isset($usedTokens[$token]) || DB::table('sales')->where('billing_token', $token)->exists());

                    $usedTokens[$token] = true;

                    DB::table('sales')
                        ->where('id', $sale->id)
                        ->update(['billing_token' => $token]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique(['billing_token']);
            $table->dropColumn('billing_token');
        });
    }

    private function newBillingToken(): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $token = '';

        for ($i = 0; $i < 7; $i++) {
            $token .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $token;
    }
};
