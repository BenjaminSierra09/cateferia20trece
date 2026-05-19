<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Services\EvolutionWhatsAppService;
use Illuminate\Support\Str;
use Throwable;

class CustomerObserver
{
    /**
     * Handle the Customer "created" event.
     */
    public function created(Customer $customer): void
    {
        $qrCode = CustomerQrCode::query()->create([
            'customer_id' => $customer->id,
            'uuid' => (string) Str::uuid(),
            'is_active' => true,
        ]);

        try {
            app(EvolutionWhatsAppService::class)->sendCustomerCredential($customer, $qrCode);
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }
}
