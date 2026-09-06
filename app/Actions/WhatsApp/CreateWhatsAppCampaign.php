<?php

namespace App\Actions\WhatsApp;

use App\Enums\WhatsAppCampaignStatus;
use App\Jobs\QueueWhatsAppCampaign;
use App\Models\Customer;
use App\Models\User;
use App\Models\WhatsAppCampaign;
use App\Support\WhatsAppCampaignParameterRenderer;
use App\Support\WhatsAppPhoneNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateWhatsAppCampaign
{
    public const MAX_RECIPIENTS = 5000;

    public function __construct(
        protected WhatsAppCampaignParameterRenderer $parameterRenderer,
        protected WhatsAppPhoneNormalizer $phoneNormalizer,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     template_name: string,
     *     template_language: string,
     *     message_preview: string,
     *     template_parameters: array<int, string>,
     *     audience: string,
     *     customer_ids?: array<int, int|string>
     * }  $data
     */
    public function execute(array $data, User $createdBy): WhatsAppCampaign
    {
        $parameterTemplates = array_values($data['template_parameters']);
        $unsupportedTokens = $this->parameterRenderer->unsupportedTokens($parameterTemplates);

        if ($unsupportedTokens !== []) {
            throw ValidationException::withMessages([
                'template_parameter_lines' => 'Variables no permitidas: '.implode(', ', $unsupportedTokens).'.',
            ]);
        }

        $unsupportedPreviewTokens = $this->parameterRenderer->unsupportedTokens([$data['message_preview']]);

        if ($unsupportedPreviewTokens !== []) {
            throw ValidationException::withMessages([
                'message_preview' => 'Variables no permitidas: '.implode(', ', $unsupportedPreviewTokens).'.',
            ]);
        }

        $customers = $this->eligibleCustomers($data);

        if ($customers->isEmpty()) {
            throw ValidationException::withMessages([
                'audience' => 'No hay contactos seleccionados con autorización vigente para recibir promociones.',
            ]);
        }

        if ($customers->count() > self::MAX_RECIPIENTS) {
            throw ValidationException::withMessages([
                'audience' => 'La campaña supera el máximo de '.number_format(self::MAX_RECIPIENTS).' contactos.',
            ]);
        }

        $campaign = DB::transaction(function () use ($customers, $data, $createdBy, $parameterTemplates): WhatsAppCampaign {
            $campaign = WhatsAppCampaign::query()->create([
                'created_by_user_id' => $createdBy->id,
                'name' => trim($data['name']),
                'template_name' => trim($data['template_name']),
                'template_language' => trim($data['template_language']),
                'message_preview' => trim($data['message_preview']),
                'template_parameters' => $parameterTemplates,
                'status' => WhatsAppCampaignStatus::Queued,
                'recipient_count' => $customers->count(),
            ]);

            foreach ($customers as $customer) {
                $campaign->recipients()->create([
                    'customer_id' => $customer->id,
                    'phone' => $this->phoneNormalizer->normalize($customer->phone),
                    'name' => $customer->name,
                    'message_preview' => $this->parameterRenderer->render([$data['message_preview']], $customer)[0],
                    'parameters' => $this->parameterRenderer->render($parameterTemplates, $customer),
                    'consented_at' => $customer->whatsapp_marketing_opted_in_at,
                    'consent_source' => $customer->whatsapp_marketing_consent_source,
                ]);
            }

            return $campaign;
        });

        QueueWhatsAppCampaign::dispatch($campaign->id);

        return $campaign;
    }

    /**
     * @param  array{audience: string, customer_ids?: array<int, int|string>}  $data
     * @return Collection<int, Customer>
     */
    private function eligibleCustomers(array $data): Collection
    {
        $query = Customer::query()
            ->eligibleForWhatsAppMarketing()
            ->select([
                'id',
                'name',
                'phone',
                'reward_balance',
                'reward_tier',
                'is_active',
                'whatsapp_marketing_opted_in_at',
                'whatsapp_marketing_opted_out_at',
                'whatsapp_marketing_consent_source',
                'whatsapp_marketing_consented_phone',
            ]);

        if ($data['audience'] === 'selected') {
            $query->whereKey(array_values(array_unique($data['customer_ids'] ?? [])));
        }

        return $query
            ->orderBy('id')
            ->get()
            ->filter(fn (Customer $customer): bool => $customer->hasWhatsAppMarketingConsent())
            ->unique(fn (Customer $customer): string => $this->phoneNormalizer->normalize($customer->phone))
            ->values();
    }
}
