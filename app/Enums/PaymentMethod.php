<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Transfer = 'transfer';
    case RewardBalance = 'reward_balance';
    case Mixed = 'mixed';
    case Debt = 'debt';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Card => 'Tarjeta',
            self::Transfer => 'Transferencia',
            self::RewardBalance => 'Saldo a favor',
            self::Mixed => 'Mixto',
            self::Debt => 'Deuda',
        };
    }
}
