<?php

namespace App\Http\Controllers;

use App\Actions\WhatsApp\RecordWhatsAppMarketingConsent;
use App\Http\Requests\PublicCustomerRegistrationRequest;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PublicCustomerRegistrationController extends Controller
{
    /**
     * Show the public customer registration form.
     */
    public function create(): View
    {
        return view('public.register', [
            'recaptchaSiteKey' => config('services.recaptcha.site_key'),
        ]);
    }

    /**
     * Store a newly registered public customer.
     */
    public function store(
        PublicCustomerRegistrationRequest $request,
        RecordWhatsAppMarketingConsent $recordMarketingConsent,
    ): RedirectResponse {
        $validated = $request->safe()->except([
            'privacy_consent',
            'whatsapp_marketing_consent',
            'recaptcha_token',
        ]);

        $customer = Customer::query()->create([
            'name' => trim($validated['name']),
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'birthday' => ($validated['birthday'] ?? null) ?: null,
            'email' => filled($validated['email'] ?? null) ? str($validated['email'])->trim()->lower()->toString() : null,
        ]);

        if ($request->boolean('whatsapp_marketing_consent')) {
            $recordMarketingConsent->grant(
                customer: $customer,
                source: 'public_registration',
                ipAddress: $request->ip(),
            );
        }

        $qrCode = $customer->qrCodes()
            ->where('is_active', true)
            ->latest('id')
            ->firstOrFail();

        return redirect()
            ->route('public.qr.show', ['uuid' => $qrCode->uuid])
            ->with('status', 'Registro completado. Este es tu QR de cliente.');
    }
}
