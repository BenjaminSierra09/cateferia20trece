<?php

namespace App\Enums;

enum CustomerDebtMovementType: string
{
    case Debt = 'debt';
    case Payment = 'payment';

    public function label(): string
    {
        return match ($this) {
            self::Debt => 'Adeudo',
            self::Payment => 'Abono',
        };
    }
}
