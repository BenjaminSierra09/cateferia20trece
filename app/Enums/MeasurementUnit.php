<?php

namespace App\Enums;

enum MeasurementUnit: string
{
    case Milliliter = 'ml';
    case Gram = 'g';
    case Piece = 'pieza';

    public function label(): string
    {
        return match ($this) {
            self::Milliliter => 'Mililitros (ml)',
            self::Gram => 'Gramos (g)',
            self::Piece => 'Piezas',
        };
    }

    /**
     * Short symbol for compact stock display.
     */
    public function abbreviation(): string
    {
        return match ($this) {
            self::Milliliter => 'ml',
            self::Gram => 'g',
            self::Piece => 'pz',
        };
    }
}
