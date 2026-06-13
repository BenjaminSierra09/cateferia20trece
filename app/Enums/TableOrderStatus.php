<?php

namespace App\Enums;

enum TableOrderStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Merged = 'merged';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierta',
            self::Closed => 'Cerrada',
            self::Merged => 'Unida',
            self::Cancelled => 'Cancelada',
        };
    }
}
