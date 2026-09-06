<?php

namespace App\Support;

class WhatsAppPhoneNormalizer
{
    public function normalize(mixed $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (mb_strlen($digits) === 10) {
            return '52'.$digits;
        }

        if (mb_strlen($digits) === 13 && str_starts_with($digits, '521')) {
            return '52'.mb_substr($digits, 3);
        }

        return $digits;
    }
}
