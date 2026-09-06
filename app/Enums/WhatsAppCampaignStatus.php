<?php

namespace App\Enums;

enum WhatsAppCampaignStatus: string
{
    case Queued = 'queued';
    case Sending = 'sending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'En cola',
            self::Sending => 'Enviando',
            self::Completed => 'Completada',
            self::Failed => 'Con error',
            self::Cancelled => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Queued => 'amber',
            self::Sending => 'sky',
            self::Completed => 'emerald',
            self::Failed => 'rose',
            self::Cancelled => 'zinc',
        };
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Queued, self::Sending], true);
    }
}
