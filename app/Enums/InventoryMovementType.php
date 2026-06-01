<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case Entrada = 'entrada';
    case Ajuste = 'ajuste';
    case TraspasoSalida = 'traspaso_salida';
    case TraspasoEntrada = 'traspaso_entrada';
    case Venta = 'venta';
    case Cancelacion = 'cancelacion';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Ajuste => 'Ajuste',
            self::TraspasoSalida => 'Traspaso (salida)',
            self::TraspasoEntrada => 'Traspaso (entrada)',
            self::Venta => 'Venta',
            self::Cancelacion => 'Cancelación',
        };
    }
}
