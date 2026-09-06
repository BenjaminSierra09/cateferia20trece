<?php

namespace App\Enums;

enum WhatsAppCampaignRecipientStatus: string
{
    case Pending = 'pending';
    case Sending = 'sending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
    case Uncertain = 'uncertain';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Sending => 'Procesando',
            self::Sent => 'Enviado',
            self::Delivered => 'Entregado',
            self::Read => 'Leído',
            self::Failed => 'Fallido',
            self::Uncertain => 'Por revisar',
            self::Skipped => 'Omitido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Sending => 'sky',
            self::Sent => 'blue',
            self::Delivered => 'emerald',
            self::Read => 'green',
            self::Failed => 'rose',
            self::Uncertain => 'orange',
            self::Skipped => 'zinc',
        };
    }

    public function isFinished(): bool
    {
        return ! in_array($this, [self::Pending, self::Sending], true);
    }
}
