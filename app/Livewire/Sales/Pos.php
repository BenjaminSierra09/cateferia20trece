<?php

namespace App\Livewire\Sales;

use App\Enums\PaymentMethod;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\BranchBeveragePriceOverride;
use App\Models\BranchBeverageSizeAvailability;
use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Models\CustomizationOption;
use App\Models\Product;
use App\Services\SaleService;
use App\Services\WorkSessionService;
use App\Support\CustomizationPriceResolver;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('POS')]
class Pos extends Component
{
    public ?int $customer_id = null;

    public string $customer_search = '';

    public bool $showCustomerResults = false;

    public string $qr_uuid = '';

    public string $payment_method = 'cash';

    public float $discount_total = 0;

    public float $reward_redeemed_total = 0;

    public string $discount_concept = '';

    public string $notes = '';

    #[Url(as: 'category', keep: true)]
    public string $selectedCategory = '';

    #[Url(as: 'search', keep: true)]
    public string $search = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $cart = [];

    /**
     * @var array<int, int|string>
     */
    public array $selectedBeverageSizes = [];

    public function addBeverage(int $beverageId, int $sizeId): void
    {
        $beverage = Beverage::query()
            ->with(['sizePrices.size'])
            ->findOrFail($beverageId);

        $sizePrice = $beverage->sizePrices->firstWhere('size_id', $sizeId);
        $currentSession = app(WorkSessionService::class)->currentFor(auth()->user());

        if ($sizePrice === null) {
            return;
        }

        if ($currentSession !== null && BranchBeverageSizeAvailability::query()
            ->where('branch_id', $currentSession->branch_id)
            ->where('beverage_id', $beverageId)
            ->where('size_id', $sizeId)
            ->where('is_available', false)
            ->exists()) {
            Flux::toast(variant: 'danger', text: 'Este tamaño no está disponible en la sucursal actual.');

            return;
        }

        $resolvedPrice = $currentSession === null
            ? (float) $sizePrice->price
            : (float) (
                BranchBeveragePriceOverride::query()
                    ->where('branch_id', $currentSession->branch_id)
                    ->where('beverage_id', $beverageId)
                    ->where('size_id', $sizeId)
                    ->value('price')
                ?? $sizePrice->price
            );

        foreach ($this->cart as $index => $item) {
            if ($item['beverage_id'] === $beverageId && $item['size_id'] === $sizeId) {
                $this->cart[$index]['quantity']++;

                return;
            }
        }

        $this->cart[] = [
            'beverage_id' => $beverageId,
            'size_id' => $sizeId,
            'item_name' => $beverage->name.' '.$sizePrice->size?->name,
            'quantity' => 1,
            'base_price' => $resolvedPrice,
            'unit_price' => $resolvedPrice,
            'size_label' => $sizePrice->size?->name ?? 'Tamaño',
            'customization_option_ids' => [],
            'special_customization_name' => '',
            'special_customization_price' => 0,
        ];
    }

    public function addSelectedBeverage(int $beverageId): void
    {
        $sizeId = $this->selectedBeverageSizeId($beverageId);

        if ($sizeId === null) {
            Flux::toast(variant: 'danger', text: 'Selecciona un tamaño antes de agregar la bebida.');

            return;
        }

        $this->selectedBeverageSizes[$beverageId] = $sizeId;

        $this->addBeverage($beverageId, $sizeId);
    }

    public function increaseQuantity(int $index): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['quantity']++;
        }
    }

    public function decreaseQuantity(int $index): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        if ($this->cart[$index]['quantity'] <= 1) {
            $this->removeItem($index);

            return;
        }

        $this->cart[$index]['quantity']--;
    }

    public function removeItem(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    public function addProduct(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        foreach ($this->cart as $index => $item) {
            if (($item['product_id'] ?? null) === $productId) {
                $this->cart[$index]['quantity']++;

                return;
            }
        }

        $unitLabel = match ($product->unit_type) {
            'gram' => 'gr',
            'kilo' => 'kg',
            default => 'pieza',
        };

        $this->cart[] = [
            'beverage_id' => null,
            'product_id' => $productId,
            'size_id' => null,
            'item_name' => $product->name,
            'quantity' => 1,
            'base_price' => (float) $product->base_price,
            'unit_price' => (float) $product->base_price,
            'size_label' => $unitLabel,
            'customization_option_ids' => [],
            'special_customization_name' => '',
            'special_customization_price' => 0,
        ];
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
        $this->showCustomerResults = false;
        $this->qr_uuid = '';

        Flux::toast(variant: 'success', text: 'Cliente identificado: '.$qrCode->customer->name);
    }

    public function clearCustomer(): void
    {
        $this->customer_id = null;
        $this->customer_search = '';
        $this->showCustomerResults = false;
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::query()->findOrFail($customerId);

        $this->customer_id = $customer->id;
        $this->customer_search = $customer->name;
        $this->showCustomerResults = false;
    }

    public function updatedCustomerSearch(string $value): void
    {
        $this->showCustomerResults = trim($value) !== '';
    }

    public function save(SaleService $saleService, WorkSessionService $workSessionService): void
    {
        $validated = $this->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'payment_method' => ['required', 'string'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'reward_redeemed_total' => ['nullable', 'numeric', 'min:0'],
            'discount_concept' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($this->cart === []) {
            $this->addError('cart', 'Agrega al menos una bebida al carrito.');

            return;
        }

        $workSession = $workSessionService->currentFor(auth()->user());

        abort_if($workSession === null, 422, 'Debes confirmar sucursal antes de registrar una venta.');

        $saleService->register([
            ...$validated,
            'items' => collect($this->cart)->map(fn (array $item): array => [
                'beverage_id' => $item['beverage_id'],
                'product_id' => $item['product_id'] ?? null,
                'size_id' => $item['size_id'],
                'quantity' => $item['quantity'],
                'customization_option_ids' => $item['customization_option_ids'] ?? [],
                'special_customization_name' => $item['special_customization_name'] ?? null,
                'special_customization_price' => $item['special_customization_price'] ?? 0,
            ])->all(),
        ], auth()->user(), $workSession);

        $this->reset('customer_id', 'customer_search', 'discount_total', 'reward_redeemed_total', 'discount_concept', 'notes', 'qr_uuid');
        $this->payment_method = PaymentMethod::Cash->value;
        $this->cart = [];

        Flux::toast(variant: 'success', text: 'Venta registrada desde POS.');
    }

    public function getSubtotalProperty(): float
    {
        return round(collect($this->cart)->sum(fn (array $item): float => $this->cartItemLineTotal($item)), 2);
    }

    public function getTotalProperty(): float
    {
        return max(round($this->subtotal - $this->discount_total - $this->reward_redeemed_total, 2), 0);
    }

    public function getSelectedCustomerProperty(): ?Customer
    {
        return $this->customer_id !== null
            ? Customer::query()->find($this->customer_id)
            : null;
    }

    /**
     * @return Collection<int, Beverage>
     */
    public function getVisibleBeveragesProperty(): Collection
    {
        return Beverage::query()
            ->with(['category', 'sizePrices.size'])
            ->where('is_active', true)
            ->when($this->selectedCategory !== '', fn ($query) => $query->where('beverage_category_id', $this->selectedCategory))
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getVisibleProductsProperty(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->get();
    }

    public function cartItemUnitPrice(array $item): float
    {
        $currentSession = app(WorkSessionService::class)->currentFor(auth()->user());
        $branchId = $currentSession?->branch_id;
        $sizeId = isset($item['size_id']) ? (int) $item['size_id'] : null;
        $priceResolver = app(CustomizationPriceResolver::class);

        $customizationTotal = CustomizationOption::query()
            ->with(['sizePrices', 'branchSizePriceOverrides' => fn ($query) => $query->when($branchId !== null, fn ($branchQuery) => $branchQuery->where('branch_id', $branchId))])
            ->whereIn('id', $item['customization_option_ids'] ?? [])
            ->get()
            ->sum(fn (CustomizationOption $option): float => $priceResolver->resolve($option, $sizeId, $branchId));

        return round(
            (float) ($item['base_price'] ?? $item['unit_price'] ?? 0)
            + (float) $customizationTotal
            + round((float) ($item['special_customization_price'] ?? 0), 2),
            2,
        );
    }

    public function cartItemLineTotal(array $item): float
    {
        return round($this->cartItemUnitPrice($item) * (int) ($item['quantity'] ?? 1), 2);
    }

    public function selectedBeverageSizeId(int $beverageId): ?int
    {
        $selectedSizeId = $this->selectedBeverageSizes[$beverageId] ?? null;

        if ($selectedSizeId !== null && $selectedSizeId !== '') {
            return (int) $selectedSizeId;
        }

        $beverage = $this->visibleBeverages->firstWhere('id', $beverageId);

        return $beverage?->sizePrices->first()?->size_id;
    }

    public function render(): View
    {
        $customerResults = collect();

        if ($this->showCustomerResults) {
            $customerResults = Customer::query()
                ->where(function ($customerQuery) {
                    $customerQuery->where('name', 'like', '%'.$this->customer_search.'%')
                        ->orWhere('phone', 'like', '%'.$this->customer_search.'%')
                        ->orWhere('email', 'like', '%'.$this->customer_search.'%');
                })
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(8)
                ->get();
        }

        $customizationGroups = CustomizationOption::query()
            ->with('type')
            ->where('is_available', true)
            ->orderBy('name')
            ->get()
            ->groupBy(fn (CustomizationOption $option): string => $option->type?->name ?? 'Sin tipo')
            ->map(fn ($options) => $options->values())
            ->all();

        return view('livewire.sales.pos', [
            'categories' => BeverageCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'customerResults' => $customerResults,
            'customizationGroups' => $customizationGroups,
            'paymentMethods' => PaymentMethod::cases(),
            'selectedCustomer' => $this->selectedCustomer,
            'visibleBeverages' => $this->visibleBeverages,
            'visibleProducts' => $this->visibleProducts,
            'currentSession' => app(WorkSessionService::class)->currentFor(auth()->user()),
        ])->layout('layouts.app');
    }
}
