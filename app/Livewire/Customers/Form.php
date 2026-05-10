<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Support\TonalpohualliCalendar;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
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

    public string $qr_uuid = '';

    public function mount(?Customer $customer = null): void
    {
        $this->customer = $customer?->exists ? $customer : null;

        if ($this->customer !== null) {
            $this->name = $this->customer->name;
            $this->phone = $this->customer->phone ?? '';
            $this->birthday = $this->customer->birthday?->toDateString() ?? '';
            $this->email = $this->customer->email ?? '';
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:'.self::PHONE_REGEX],
            'birthday' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
        ], $this->messages(), $this->validationAttributes());

        $customer = Customer::query()->updateOrCreate(
            ['id' => $this->customer?->id],
            $validated,
        );

        $this->customer = $customer;

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

    public function render(): View
    {
        return view('livewire.customers.form', [
            'linkedQrCodes' => $this->customer?->qrCodes()->latest()->get() ?? collect(),
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
        ];
    }
}
