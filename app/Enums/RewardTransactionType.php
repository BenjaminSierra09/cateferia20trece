<?php

namespace App\Enums;

enum RewardTransactionType: string
{
    case Earned = 'earned';
    case Redeemed = 'redeemed';
    case ManualAdjustment = 'manual_adjustment';
    case Cancellation = 'cancellation';

    public function label(): string
    {
        return match ($this) {
            self::Earned => 'Abono',
            self::Redeemed => 'Uso',
            self::ManualAdjustment => 'Ajuste manual',
            self::Cancellation => 'Cancelación',
        };
    }
}
