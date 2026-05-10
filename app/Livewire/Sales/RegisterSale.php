<?php

namespace App\Livewire\Sales;

use App\Enums\PaymentMethod;
use App\Models\Beverage;
use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Models\CustomizationOption;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Size;
use App\Services\SaleService;
use App\Services\WorkSessionService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Venta manual')]
class RegisterSale extends Component
{
    public ?int $customer_id = null;

    public string $customer_search = '';

    public string $qr_uuid = '';

    public string $payment_method = 'cash';

    public float $discount_total = 0;

    public float $reward_redeemed_total = 0;

    public string $discount_concept = '';

    public string $notes = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $items = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->items = [$this->emptyItem()];
    }

    /**
     * Add a new item row.
     */
    public function addItem(): void
    {
        $this->items[] = $this->emptyItem();
    }

    /**
     * Remove an item row.
     */
    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->items[] = $this->emptyItem();
        }
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::query()->findOrFail($customerId);

        $this->customer_id = $customer->id;
        $this->customer_search = $customer->name;
    }

    public function clearCustomer(): void
    {
        $this->customer_id = null;
        $this->customer_search = '';
        $this->qr_uuid = '';
    }

    public function assignCustomerByQr(): void
    {
        $validated = $this->validate([
            'qr_uuid' => ['required', 'uuid'],
        ]);

        $qrCode = CustomerQrCode::query()
            ->with('customer')
            ->where('uuid', $validated['qr_uuid'])
            ->where('is_active', true)
            ->first();

        if ($qrCode === null || $qrCode->customer === null) {
            Flux::toast(variant: 'danger', text: 'No encontramos un cliente con ese QR.');

            return;
        }

        $qrCode->update(['last_scanned_at' => now()]);
        $this->customer_id = $qrCode->customer_id;
        $this->customer_search = $qrCode->customer->name;
        $this->qr_uuid = '';

        Flux::toast(variant: 'success', text: 'Cliente identificado: '.$qrCode->customer->name);
    }

    /**
     * Persist the sale.
     */
    public function save(SaleService $saleService, WorkSessionService $workSessionService): void
    {
        $validated = $this->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'payment_method' => ['required', 'string'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'reward_redeemed_total' => ['nullable', 'numeric', 'min:0'],
            'discount_concept' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.beverage_id' => ['nullable', 'integer', 'exists:beverages,id'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.size_id' => ['nullable', 'integer', 'exists:sizes,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.customization_option_ids' => ['nullable', 'array'],
            'items.*.customization_option_ids.*' => ['integer', 'exists:customization_options,id'],
            'items.*.special_customization_name' => ['nullable', 'string', 'max:255'],
            'items.*.special_customization_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.special_instructions' => ['nullable', 'string'],
        ]);

        $workSession = $workSessionService->currentFor(auth()->user());

        abort_if($workSession === null, 422, 'Debes confirmar sucursal antes de registrar una venta.');

        $saleService->register($validated, auth()->user(), $workSession);

        $this->reset('customer_id', 'customer_search', 'discount_total', 'reward_redeemed_total', 'discount_concept', 'notes', 'qr_uuid');
        $this->payment_method = PaymentMethod::Cash->value;
        $this->items = [$this->emptyItem()];

        Flux::toast(variant: 'success', text: 'Venta registrada.');
    }

    /**
     * Return a default empty item payload.
     *
     * @return array<string, mixed>
     */
    protected function emptyItem(): array
    {
        return [
            'beverage_id' => null,
            'product_id' => null,
            'size_id' => null,
            'item_name' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'customization_option_ids' => [],
            'special_customization_name' => '',
            'special_customization_price' => 0,
            'special_instructions' => '',
        ];
    }

    /**
     * Render the sales page.
     */
    public function render(): View
    {
        $customerResults = Customer::query()
            ->when($this->customer_search !== '', function ($query) {
                $query->where(function ($customerQuery) {
                    $customerQuery->where('name', 'like', '%'.$this->customer_search.'%')
                        ->orWhere('phone', 'like', '%'.$this->customer_search.'%')
                        ->orWhere('email', 'like', '%'.$this->customer_search.'%');
                });
            })
            ->orderBy('name')
            ->limit(8)
            ->get();

        return view('livewire.sales.register-sale', [
            'customerResults' => $customerResults,
            'selectedCustomer' => $this->customer_id !== null ? Customer::query()->find($this->customer_id) : null,
            'beverages' => Beverage::query()->with(['category', 'sizePrices.size'])->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'sizes' => Size::query()->where('is_active', true)->orderBy('name')->get(),
            'customizationOptions' => CustomizationOption::query()->with('type')->where('is_available', true)->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::cases(),
            'recentSales' => Sale::query()->with(['customer', 'branch'])->latest('sold_at')->limit(10)->get(),
            'currentSession' => app(WorkSessionService::class)->currentFor(auth()->user()),
        ])->layout('layouts.app');
    }
}
