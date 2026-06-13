<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Accounting = 'accounting';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Accounting => 'Contabilidad',
            self::Employee => 'Empleado',
        };
    }
}
