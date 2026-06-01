<?php

namespace App\Ai\Tools;

use App\Models\Customer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CheckCustomerBalanceTool implements Tool
{
    public function __construct(private Customer $customer) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Consulta el saldo del cliente: saldo de recompensas, saldo disponible tras cubrir deuda y deuda pendiente. No recibe parámetros.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $money = static fn (float $amount): string => '$'.number_format($amount, 2);

        return (string) json_encode([
            'cliente' => $this->customer->name,
            'saldo_recompensas' => $money((float) $this->customer->reward_balance),
            'saldo_disponible' => $money($this->customer->availableRewardBalance()),
            'deuda_pendiente' => $money($this->customer->debtBalance()),
            'tiene_deuda' => $this->customer->hasDebt(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
