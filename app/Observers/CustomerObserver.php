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

    /**
     * Handle the Customer "updated" event.
     */
    public function updated(Customer $customer): void
    {
        if (! $customer->wasChanged('phone')) {
            return;
        }

        if ($this->normalizePhoneNumber($customer->getOriginal('phone')) === $this->normalizePhoneNumber($customer->phone)) {
            return;
        }

        $qrCode = $customer->qrCodes()
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if (! $qrCode instanceof CustomerQrCode) {
            return;
        }

        try {
            app(EvolutionWhatsAppService::class)->sendCustomerCredential($customer, $qrCode);
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }

    protected function normalizePhoneNumber(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }
}
