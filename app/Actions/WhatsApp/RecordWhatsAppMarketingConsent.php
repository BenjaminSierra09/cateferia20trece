<?php

namespace App\Actions\WhatsApp;

use App\Models\Customer;
use App\Models\User;
use App\Support\WhatsAppPhoneNormalizer;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordWhatsAppMarketingConsent
{
    public const CONSENT_TEXT_VERSION = '2026-09-06';

    public function __construct(
        protected WhatsAppPhoneNormalizer $phoneNormalizer,
    ) {}

    public function grant(
        Customer $customer,
        string $source,
        ?User $recordedBy = null,
        ?string $ipAddress = null,
    ): void {
        $phone = $this->phoneNormalizer->normalize($customer->phone);

        if ($phone === '') {
            throw new InvalidArgumentException('El cliente necesita un teléfono válido para autorizar campañas.');
        }

        if ($customer->hasWhatsAppMarketingConsent()
            && $customer->whatsapp_marketing_consented_phone === $phone) {
            return;
        }

        DB::transaction(function () use ($customer, $source, $recordedBy, $ipAddress, $phone): void {
            $occurredAt = now();

            $customer->forceFill([
                'whatsapp_marketing_opted_in_at' => $occurredAt,
                'whatsapp_marketing_opted_out_at' => null,
                'whatsapp_marketing_consent_source' => $source,
                'whatsapp_marketing_consent_version' => self::CONSENT_TEXT_VERSION,
                'whatsapp_marketing_consented_phone' => $phone,
            ])->save();

            $customer->whatsappMarketingConsents()->create([
                'recorded_by_user_id' => $recordedBy?->id,
                'phone' => $phone,
                'status' => 'granted',
                'source' => $source,
                'consent_text_version' => self::CONSENT_TEXT_VERSION,
                'ip_address' => $ipAddress,
                'occurred_at' => $occurredAt,
            ]);
        });
    }

    public function revoke(
        Customer $customer,
        string $source,
        ?User $recordedBy = null,
        ?string $ipAddress = null,
    ): void {
        if ($customer->whatsapp_marketing_opted_in_at === null
            || ($customer->whatsapp_marketing_opted_out_at !== null
                && $customer->whatsapp_marketing_opted_out_at->greaterThanOrEqualTo($customer->whatsapp_marketing_opted_in_at))) {
            return;
        }

        DB::transaction(function () use ($customer, $source, $recordedBy, $ipAddress): void {
            $occurredAt = now();
            $phone = (string) $customer->whatsapp_marketing_consented_phone;

            $customer->forceFill([
                'whatsapp_marketing_opted_out_at' => $occurredAt,
            ])->save();

            $customer->whatsappMarketingConsents()->create([
                'recorded_by_user_id' => $recordedBy?->id,
                'phone' => $phone,
                'status' => 'revoked',
                'source' => $source,
                'consent_text_version' => (string) ($customer->whatsapp_marketing_consent_version ?: self::CONSENT_TEXT_VERSION),
                'ip_address' => $ipAddress,
                'occurred_at' => $occurredAt,
            ]);
        });
    }
}
