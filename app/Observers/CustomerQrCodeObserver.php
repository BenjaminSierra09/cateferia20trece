<?php

namespace App\Observers;

use App\Models\CustomerQrCode;
use Illuminate\Support\Str;

class CustomerQrCodeObserver
{
    /**
     * Handle the CustomerQrCode "saving" event.
     */
    public function saving(CustomerQrCode $customerQrCode): void
    {
        $customerQrCode->uuid = Str::lower(trim($customerQrCode->uuid));
    }
}
