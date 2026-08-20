<?php

namespace App\Enums;

enum WhatsAppMessageDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
}
