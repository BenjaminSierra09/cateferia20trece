<div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <flux:heading>Ventas</flux:heading>
            <flux:text>Registra bebidas, productos y clientes desde el POS.</flux:text>
        </div>

        @if ($currentSession)
            <flux:badge color="emerald" size="lg">
                {{ $currentSession->branch?->name ?? 'Sucursal activa' }}
            </flux:badge>
        @else
            <flux:badge color="amber" size="lg">Sin sucursal activa</flux:badge>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <div class="space-y-4">
            <flux:card class="space-y-4">
                <div class="grid gap-4 md:grid-cols-[220px_minmax(0,1fr)]">
                    <flux:select wire:model.live="selectedCategory" label="Categoría">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        label="Buscar"
                        placeholder="Bebida o producto"
                    />
                </div>
            </flux:card>

            <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                @foreach ($visibleBeverages as $beverage)
                    <flux:card class="space-y-4" wire:key="pos-beverage-{{ $beverage->id }}">
                        <div>
                            <flux:heading size="lg">{{ $beverage->name }}</flux:heading>
                            <flux:text>{{ $beverage->category?->name ?? 'Bebida' }}</flux:text>
                        </div>

                        @if ($beverage->sizePrices->isNotEmpty())
                            <flux:select wire:model.live="selectedBeverageSizes.{{ $beverage->id }}" label="Tamaño">
                                @foreach ($beverage->sizePrices as $sizePrice)
                                    <option value="{{ $sizePrice->size_id }}">
                                        {{ $sizePrice->size?->name ?? 'Tamaño' }} · ${{ number_format($sizePrice->price, 2) }}
                                    </option>
                                @endforeach
                            </flux:select>

                            <flux:button
                                type="button"
                                variant="primary"
                                icon="plus"
                                wire:click="addSelectedBeverage({{ $beverage->id }})"
                                class="w-full"
                            >
                                Agregar
                            </flux:button>
                        @else
                            <flux:callout color="amber" icon="exclamation-triangle">
                                Sin tamaños disponibles.
                            </flux:callout>
                        @endif
                    </flux:card>
                @endforeach

                @foreach ($visibleProducts as $product)
                    <flux:card class="space-y-4" wire:key="pos-product-{{ $product->id }}">
                        <div>
                            <flux:heading size="lg">{{ $product->name }}</flux:heading>
                            <flux:text>{{ $product->description ?: 'Producto' }}</flux:text>
                        </div>

                        <div class="text-lg font-semibold">${{ number_format($product->base_price, 2) }}</div>

                        <flux:button
                            type="button"
                            variant="primary"
                            icon="plus"
                            wire:click="addProduct({{ $product->id }})"
                            class="w-full"
                        >
                            Agregar
                        </flux:button>
                    </flux:card>
                @endforeach
            </div>
        </div>

        <flux:card class="space-y-5">
            <div>
                <flux:heading>Carrito</flux:heading>
                <flux:text>{{ count($cart) }} artículos</flux:text>
            </div>

            <div class="space-y-3">
                <flux:input
                    wire:model.live.debounce.300ms="customer_search"
                    label="Cliente"
                    placeholder="Buscar cliente"
                />

                @if ($showCustomerResults && $customerResults->isNotEmpty())
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                        @foreach ($customerResults as $customer)
                            <button
                                type="button"
                                wire:click="selectCustomer({{ $customer->id }})"
                                class="flex w-full items-center justify-between gap-3 border-b border-zinc-100 px-3 py-2 text-left last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800"
                            >
                                <span>{{ $customer->name }}</span>
                                <span class="text-xs text-zinc-500">Seleccionar</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                @if ($selectedCustomer)
                    <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
                        <span class="font-medium">{{ $selectedCustomer->name }}</span>
                        <flux:button type="button" variant="ghost" icon="trash" wire:click="clearCustomer">
                            Quitar
                        </flux:button>
                    </div>
                @endif
            </div>

            <div class="space-y-3">
                @forelse ($cart as $index => $item)
                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="cart-item-{{ $index }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ $item['item_name'] }}</div>
                                <div class="text-sm text-zinc-500">{{ $item['size_label'] ?? 'Artículo' }}</div>
                            </div>
                            <div class="text-right font-semibold">${{ number_format($this->cartItemLineTotal($item), 2) }}</div>
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <flux:button type="button" size="sm" icon="minus" wire:click="decreaseQuantity({{ $index }})" />
                                <span class="w-8 text-center font-semibold">{{ $item['quantity'] }}</span>
                                <flux:button type="button" size="sm" icon="plus" wire:click="increaseQuantity({{ $index }})" />
                            </div>

                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $index }})" />
                        </div>
                    </div>
                @empty
                    <flux:callout icon="shopping-cart" color="zinc">
                        Agrega bebidas o productos para registrar la venta.
                    </flux:callout>
                @endforelse
            </div>

            <div class="grid gap-3">
                <flux:select wire:model.live="payment_method" label="Método de pago">
                    @foreach ($paymentMethods as $method)
                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                    @endforeach
                </flux:select>

                <div class="grid gap-3 md:grid-cols-2">
                    <flux:input wire:model.live="discount_total" type="number" min="0" step="0.01" label="Descuento" />
                    <flux:input wire:model.live="reward_redeemed_total" type="number" min="0" step="0.01" label="Saldo usado" />
                </div>

                <flux:input wire:model="discount_concept" label="Concepto de descuento" />
                <flux:textarea wire:model="notes" label="Notas" rows="2" />
            </div>

            <div class="space-y-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span>${{ number_format($this->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-lg font-semibold">
                    <span>Total</span>
                    <span>${{ number_format($this->total, 2) }}</span>
                </div>
            </div>

            @error('cart')
                <flux:error>{{ $message }}</flux:error>
            @enderror

            <div class="flex gap-3">
                <flux:button type="button" variant="ghost" icon="x-mark" wire:click="clearCart" class="flex-1">
                    Limpiar
                </flux:button>
                <flux:button type="button" variant="primary" icon="check" wire:click="save" class="flex-1">
                    Registrar venta
                </flux:button>
            </div>
        </flux:card>
    </div>
</div>
