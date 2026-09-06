<?php

namespace App\Livewire\WhatsApp;

use App\Actions\WhatsApp\CancelWhatsAppCampaign;
use App\Actions\WhatsApp\CreateWhatsAppCampaign;
use App\Actions\WhatsApp\RetryWhatsAppCampaignFailures;
use App\Enums\WhatsAppCampaignRecipientStatus;
use App\Enums\WhatsAppCampaignStatus;
use App\Models\Customer;
use App\Models\User;
use App\Models\WhatsAppCampaign;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Campañas de WhatsApp')]
class Campaigns extends Component
{
    public string $name = '';

    public string $template_name = '';

    public string $template_language = 'es_MX';

    public string $message_preview = '';

    public string $template_parameter_lines = '';

    public string $audience = 'all';

    /** @var array<int, int|string> */
    public array $selected_customer_ids = [];

    public bool $consent_confirmed = false;

    public ?int $selectedCampaignId = null;

    public function mount(): void
    {
        $this->authorizeWhatsApp();
        $this->template_language = (string) config('services.whatsapp.templates.language', 'es_MX');
        $this->selectedCampaignId = WhatsAppCampaign::query()->latest('id')->value('id');
    }

    /**
     * @return Collection<int, WhatsAppCampaign>
     */
    #[Computed]
    public function campaigns(): Collection
    {
        return WhatsAppCampaign::query()
            ->with('createdBy:id,name')
            ->withCount([
                'recipients',
                'recipients as pending_recipients_count' => fn ($query) => $query->whereIn('status', [
                    WhatsAppCampaignRecipientStatus::Pending,
                    WhatsAppCampaignRecipientStatus::Sending,
                ]),
                'recipients as successful_recipients_count' => fn ($query) => $query->whereIn('status', [
                    WhatsAppCampaignRecipientStatus::Sent,
                    WhatsAppCampaignRecipientStatus::Delivered,
                    WhatsAppCampaignRecipientStatus::Read,
                ]),
                'recipients as delivered_recipients_count' => fn ($query) => $query->whereIn('status', [
                    WhatsAppCampaignRecipientStatus::Delivered,
                    WhatsAppCampaignRecipientStatus::Read,
                ]),
                'recipients as read_recipients_count' => fn ($query) => $query->where('status', WhatsAppCampaignRecipientStatus::Read),
                'recipients as failed_recipients_count' => fn ($query) => $query->where('status', WhatsAppCampaignRecipientStatus::Failed),
                'recipients as uncertain_recipients_count' => fn ($query) => $query->where('status', WhatsAppCampaignRecipientStatus::Uncertain),
                'recipients as skipped_recipients_count' => fn ($query) => $query->where('status', WhatsAppCampaignRecipientStatus::Skipped),
            ])
            ->latest('id')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, Customer>
     */
    #[Computed]
    public function eligibleCustomers(): Collection
    {
        return Customer::query()
            ->eligibleForWhatsAppMarketing()
            ->select([
                'id',
                'name',
                'phone',
                'is_active',
                'whatsapp_marketing_opted_in_at',
                'whatsapp_marketing_opted_out_at',
                'whatsapp_marketing_consented_phone',
            ])
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->filter(fn (Customer $customer): bool => $customer->hasWhatsAppMarketingConsent())
            ->values();
    }

    #[Computed]
    public function eligibleCustomerCount(): int
    {
        return Customer::query()
            ->eligibleForWhatsAppMarketing()
            ->get()
            ->filter(fn (Customer $customer): bool => $customer->hasWhatsAppMarketingConsent())
            ->count();
    }

    #[Computed]
    public function activeCampaignCount(): int
    {
        return WhatsAppCampaign::query()
            ->whereIn('status', [
                WhatsAppCampaignStatus::Queued,
                WhatsAppCampaignStatus::Sending,
            ])
            ->count();
    }

    #[Computed]
    public function selectedCampaign(): ?WhatsAppCampaign
    {
        if ($this->selectedCampaignId === null) {
            return null;
        }

        return WhatsAppCampaign::query()
            ->with([
                'createdBy:id,name',
                'recipients' => fn ($query) => $query
                    ->with('customer:id,name,phone')
                    ->orderBy('id')
                    ->limit(100),
            ])
            ->withCount([
                'recipients',
                'recipients as pending_recipients_count' => fn ($query) => $query->where('status', WhatsAppCampaignRecipientStatus::Pending),
                'recipients as successful_recipients_count' => fn ($query) => $query->whereIn('status', [
                    WhatsAppCampaignRecipientStatus::Sent,
                    WhatsAppCampaignRecipientStatus::Delivered,
                    WhatsAppCampaignRecipientStatus::Read,
                ]),
                'recipients as delivered_recipients_count' => fn ($query) => $query->whereIn('status', [
                    WhatsAppCampaignRecipientStatus::Delivered,
                    WhatsAppCampaignRecipientStatus::Read,
                ]),
                'recipients as read_recipients_count' => fn ($query) => $query->where('status', WhatsAppCampaignRecipientStatus::Read),
                'recipients as failed_recipients_count' => fn ($query) => $query->where('status', WhatsAppCampaignRecipientStatus::Failed),
                'recipients as uncertain_recipients_count' => fn ($query) => $query->where('status', WhatsAppCampaignRecipientStatus::Uncertain),
                'recipients as skipped_recipients_count' => fn ($query) => $query->where('status', WhatsAppCampaignRecipientStatus::Skipped),
            ])
            ->find($this->selectedCampaignId);
    }

    public function createCampaign(CreateWhatsAppCampaign $createCampaign): void
    {
        $user = $this->authorizeWhatsApp();
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'template_name' => ['required', 'string', 'max:512', 'regex:/^[a-z0-9_]+$/'],
            'template_language' => ['required', 'string', 'max:16', 'regex:/^[a-z]{2,3}(?:_[A-Z]{2})?$/'],
            'message_preview' => ['required', 'string', 'max:4096'],
            'template_parameter_lines' => ['nullable', 'string', 'max:4000'],
            'audience' => ['required', Rule::in(['all', 'selected'])],
            'selected_customer_ids' => ['array', 'max:5000'],
            'selected_customer_ids.*' => ['integer', Rule::exists(Customer::class, 'id')],
            'consent_confirmed' => ['accepted'],
        ], [
            'template_name.regex' => 'El nombre de Meta sólo puede usar minúsculas, números y guiones bajos.',
            'template_language.regex' => 'Usa un idioma válido, por ejemplo es_MX.',
            'consent_confirmed.accepted' => 'Confirma que usarás una plantilla de marketing aprobada por Meta.',
        ]);

        if ($this->audience === 'selected' && $this->selected_customer_ids === []) {
            $this->addError('audience', 'Selecciona al menos un contacto.');

            return;
        }

        $parameters = collect(preg_split('/\R/u', $validated['template_parameter_lines']) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter(fn (string $line): bool => $line !== '')
            ->values();

        if ($parameters->count() > 20) {
            $this->addError('template_parameter_lines', 'Puedes usar hasta 20 parámetros de cuerpo.');

            return;
        }

        $campaign = $createCampaign->execute([
            'name' => $validated['name'],
            'template_name' => $validated['template_name'],
            'template_language' => $validated['template_language'],
            'message_preview' => $validated['message_preview'],
            'template_parameters' => $parameters->all(),
            'audience' => $validated['audience'],
            'customer_ids' => $validated['selected_customer_ids'],
        ], $user);

        $this->reset([
            'name',
            'template_name',
            'message_preview',
            'template_parameter_lines',
            'selected_customer_ids',
            'consent_confirmed',
        ]);
        $this->template_language = (string) config('services.whatsapp.templates.language', 'es_MX');
        $this->audience = 'all';
        $this->selectedCampaignId = $campaign->id;
        $this->resetErrorBag();
        unset($this->campaigns, $this->selectedCampaign, $this->activeCampaignCount);

        Flux::toast(variant: 'success', text: 'Campaña creada y puesta en cola.');
    }

    public function selectCampaign(int $campaignId): void
    {
        $this->authorizeWhatsApp();
        $this->selectedCampaignId = WhatsAppCampaign::query()->findOrFail($campaignId)->id;
        unset($this->selectedCampaign);
    }

    public function cancelCampaign(int $campaignId, CancelWhatsAppCampaign $cancelCampaign): void
    {
        $this->authorizeWhatsApp();
        $cancelCampaign->execute(WhatsAppCampaign::query()->findOrFail($campaignId));
        unset($this->campaigns, $this->selectedCampaign, $this->activeCampaignCount);
        Flux::toast(variant: 'success', text: 'Campaña cancelada. Los envíos pendientes fueron omitidos.');
    }

    public function retryFailures(int $campaignId, RetryWhatsAppCampaignFailures $retryFailures): void
    {
        $this->authorizeWhatsApp();
        $count = $retryFailures->execute(WhatsAppCampaign::query()->findOrFail($campaignId));
        unset($this->campaigns, $this->selectedCampaign, $this->activeCampaignCount);
        Flux::toast(variant: 'success', text: "Se pusieron en cola {$count} envíos pendientes o fallidos.");
    }

    public function render(): View
    {
        return view('livewire.whats-app.campaigns')->layout('layouts.app');
    }

    private function authorizeWhatsApp(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->canManageWhatsApp(), 403);

        return $user;
    }
}
