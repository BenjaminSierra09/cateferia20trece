<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\CustomerQrCode;
use Illuminate\Support\Str;

class CustomerObserver
{
    /**
     * Handle the Customer "created" event.
     */
    public function created(Customer $customer): void
    {
        CustomerQrCode::query()->create([
            'customer_id' => $customer->id,
            'uuid' => (string) Str::uuid(),
            'is_active' => true,
        ]);
    }
}
