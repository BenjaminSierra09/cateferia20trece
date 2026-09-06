<?php

namespace App\Livewire\Customers;

use App\Actions\WhatsApp\RecordWhatsAppMarketingConsent;
use App\Enums\CustomerDebtMovementType;
use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Models\User;
use App\Services\CustomerDebtService;
use App\Services\WorkSessionService;
use App\Support\TonalpohualliCalendar;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Cliente')]
class Form extends Component
{
    protected const PHONE_REGEX = '/^\+[1-9]\d{7,14}$/';

    public ?Customer $customer = null;

    public string $name = '';

    public string $phone = '';

    public string $birthday = '';

    public string $email = '';

    public string $notes = '';

    public bool $whatsapp_marketing_consent = false;

    public string $qr_uuid = '';

    public string $debt_amount = '';

    public string $debt_notes = '';

    public function mount(?Customer $customer = null): void
    {
        $this->customer = $customer?->exists ? $customer : null;

        if ($this->customer !== null) {
            $this->name = $this->customer->name;
            $this->phone = $this->customer->phone ?? '';
            $this->birthday = $this->customer->birthday?->toDateString() ?? '';
            $this->email = $this->customer->email ?? '';
            $this->notes = $this->customer->notes ?? '';
            $this->whatsapp_marketing_consent = $this->customer->hasWhatsAppMarketingConsent();
        }
    }

    public function save(RecordWhatsAppMarketingConsent $recordMarketingConsent): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'nullable',
                Rule::requiredIf($this->whatsapp_marketing_consent),
                'string',
                'max:50',
                'regex:'.self::PHONE_REGEX,
            ],
            'birthday' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'whatsapp_marketing_consent' => ['boolean'],
        ], $this->messages(), $this->validationAttributes());

        unset($validated['whatsapp_marketing_consent']);

        foreach (['phone', 'birthday', 'email', 'notes'] as $field) {
            $value = trim($validated[$field] ?? '');
            $validated[$field] = $value !== '' ? $value : null;
        }

        $customer = Customer::query()->updateOrCreate(
            ['id' => $this->customer?->id],
            $validated,
        );

        $this->customer = $customer;

        $user = auth()->user();

        if ($user instanceof User && $user->canManageWhatsApp()) {
            if ($this->whatsapp_marketing_consent) {
                $recordMarketingConsent->grant($customer, 'dashboard_customer', $user);
            } else {
                $recordMarketingConsent->revoke($customer, 'dashboard_customer', $user);
            }
        }

        Flux::toast(variant: 'success', text: 'Cliente guardado.');

        $this->redirectRoute('dashboard.customers.edit', ['customer' => $customer], navigate: true);
    }

    public function attachQrCode(): void
    {
        abort_if($this->customer === null, 422, 'Primero guarda el cliente.');

        $validated = $this->validate([
            'qr_uuid' => ['required', 'uuid'],
        ]);

        CustomerQrCode::query()->updateOrCreate(
            ['uuid' => $validated['qr_uuid']],
            ['customer_id' => $this->customer->id, 'is_active' => true],
        );

        $this->qr_uuid = '';

        Flux::toast(variant: 'success', text: 'QR vinculado.');
    }

    public function registerDebt(): void
    {
        $this->registerDebtMovement(CustomerDebtMovementType::Debt);
    }

    public function registerPayment(): void
    {
        $this->registerDebtMovement(CustomerDebtMovementType::Payment);
    }

    public function render(): View
    {
        return view('livewire.customers.form', [
            'linkedQrCodes' => $this->customer?->qrCodes()->latest()->get() ?? collect(),
            'debtMovements' => $this->customer?->debtMovements()->with(['user', 'branch'])->get() ?? collect(),
            'tonalpohualli' => $this->birthday !== ''
                ? app(TonalpohualliCalendar::class)->resolve(CarbonImmutable::parse($this->birthday, config('app.timezone')))
                : null,
        ])->layout('layouts.app');
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'phone.regex' => 'Captura el teléfono en formato internacional, por ejemplo +524151234567.',
        ];
    }

    /**
     * Get validation attribute labels.
     *
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'phone' => 'teléfono',
            'notes' => 'notas',
            'debt_amount' => 'monto',
        ];
    }

    protected function registerDebtMovement(CustomerDebtMovementType $type): void
    {
        abort_if($this->customer === null, 422, 'Primero guarda el cliente.');

        $validated = $this->validate([
            'debt_amount' => ['required', 'numeric', 'gt:0'],
            'debt_notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            app(CustomerDebtService::class)->register(
                customer: $this->customer,
                type: $type,
                amount: (float) $validated['debt_amount'],
                notes: $validated['debt_notes'] !== '' ? $validated['debt_notes'] : null,
                user: auth()->user(),
                branchId: app(WorkSessionService::class)->currentFor(auth()->user())?->branch_id,
            );
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'debt_amount' => [$exception->getMessage()],
            ]);
        }

        $this->customer = $this->customer->fresh();
        $this->debt_amount = '';
        $this->debt_notes = '';

        Flux::toast(
            variant: 'success',
            text: $type === CustomerDebtMovementType::Debt ? 'Adeudo registrado.' : 'Abono registrado.',
        );
    }
}
