<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Cashier = 'cashier';
    case Barista = 'barista';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Manager => 'Gerente',
            self::Cashier => 'Cajero',
            self::Barista => 'Barista',
        };
    }
}
