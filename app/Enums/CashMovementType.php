<?php

namespace App\Enums;

enum CashMovementType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Ingreso adicional',
            self::Expense => 'Gasto',
            self::CashIn => 'Entrada de efectivo',
            self::CashOut => 'Salida de efectivo',
        };
    }

    public function direction(): int
    {
        return match ($this) {
            self::Income, self::CashIn => 1,
            self::Expense, self::CashOut => -1,
        };
    }
}
