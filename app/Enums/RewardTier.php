<?php

namespace App\Enums;

enum RewardTier: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';

    public function label(): string
    {
        return match ($this) {
            self::Bronze => 'Bronce',
            self::Silver => 'Plata',
            self::Gold => 'Oro',
        };
    }

    public function percentage(): int
    {
        return match ($this) {
            self::Bronze => 5,
            self::Silver => 10,
            self::Gold => 15,
        };
    }
}
