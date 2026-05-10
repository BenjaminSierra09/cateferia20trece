<div class="grid gap-6 xl:min-h-0 xl:h-[calc(100vh-4rem)] xl:grid-cols-[minmax(0,1.65fr)_420px] xl:overflow-hidden">
    <div class="flex min-h-0 flex-col gap-6 overflow-hidden">
        <flux:card class="space-y-5 overflow-hidden border-zinc-200 bg-gradient-to-br from-orange-50 via-white to-amber-50 dark:border-zinc-700 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-800">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <flux:input wire:model.live.debounce.300ms="search" label="Buscar bebida" placeholder="Latte, matcha, chai..." />
                <div class="flex flex-wrap gap-2">
                    <flux:button
                        type="button"
                        variant="ghost"
                        icon="bars-3-center-left"
                        class="hidden lg:inline-flex"
                        x-on:click="$dispatch('toggle-pos-sidebar')"
                    >
                        Menú lateral
                    </flux:button>
                    <flux:button :href="route('dashboard.sales.index')" variant="ghost" wire:navigate>Historial</flux:button>
                    <flux:button :href="route('dashboard.sales.create')" variant="ghost" wire:navigate>Venta manual</flux:button>
                </div>
            </div>
        </flux:card>

        <div class="min-h-0 flex-1 overflow-y-auto pr-2">
            <div class="sticky top-0 z-10 -mx-1 mb-5 bg-white/90 px-1 pb-3 pt-1 backdrop-blur dark:bg-zinc-800/90">
                <div class="flex gap-3 overflow-x-auto pb-1">
                    <flux:button
                        type="button"
                        variant="{{ $selectedCategory === '' ? 'primary' : 'ghost' }}"
                        class="shrink-0"
                        wire:click="$set('selectedCategory', '')"
                    >
                        Todo el menú
                    </flux:button>

                    @foreach ($categories as $category)
                        <flux:button
                            type="button"
                            variant="{{ (string) $category->id === $selectedCategory ? 'primary' : 'ghost' }}"
                            class="shrink-0"
                            wire:click="$set('selectedCategory', '{{ $category->id }}')"
                        >
                            {{ $category->name }}
                        </flux:button>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 2xl:grid-cols-4">
                @forelse ($visibleBeverages as $beverage)
                    <flux:card wire:key="pos-beverage-{{ $beverage->id }}" class="overflow-hidden border-zinc-200 p-0 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-zinc-700">
                        <div class="aspect-[5/4] bg-gradient-to-br from-amber-100 via-orange-50 to-lime-100 dark:from-zinc-800 dark:via-zinc-900 dark:to-zinc-800">
                            @if ($beverage->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($beverage->image_path) }}" alt="{{ $beverage->name }}" class="h-full w-full object-cover" />
                            @else
                                <div class="flex h-full items-center justify-center text-4xl font-semibold text-orange-500">
                                    {{ \Illuminate\Support\Str::of($beverage->name)->explode(' ')->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('') }}
                                </div>
                            @endif
                        </div>

                        <div class="space-y-4 p-4">
                              <flux:badge color="amber">{{ $beverage->name }}</flux:badge>

                            @php
                                $selectedSizeId = $this->selectedBeverageSizeId($beverage->id);
                                $selectedSizePrice = $beverage->sizePrices->firstWhere('size_id', $selectedSizeId);
                            @endphp

                            <div class="space-y-3">
                                <flux:radio.group
                                    wire:model.live="selectedBeverageSizes.{{ $beverage->id }}"
                                    label="Tamaños" variant="pills"
                                >
                                    @foreach ($beverage->sizePrices as $sizePrice)
                                        <flux:radio
                                            wire:key="pos-beverage-size-{{ $beverage->id }}-{{ $sizePrice->size_id }}"
                                            value="{{ $sizePrice->size_id }}"
                                            :checked="$selectedSizeId === $sizePrice->size_id"
                                            :label="$sizePrice->size?->name ?? 'Tamaño'"
                                            :description="'$'.number_format($sizePrice->price, 2)"
                                        />
                                    @endforeach
                                </flux:radio.group>

                                <flux:button
                                    type="button"
                                    size="sm"
                                    variant="primary"
                                    class="w-full justify-between"
                                    wire:click="addSelectedBeverage({{ $beverage->id }})"
                                >
                                    <span>Agregar al carrito</span>
                                    <span>
                                        {{ $selectedSizePrice?->size?->name ?? 'Tamaño' }}
                                        ${{ number_format((float) ($selectedSizePrice?->price ?? 0), 2) }}
                                    </span>
                                </flux:button>
                            </div>
                        </div>
                    </flux:card>
                @empty
                    <flux:card class="sm:col-span-2 2xl:col-span-3">
                        <flux:callout icon="information-circle" color="sky">No encontramos bebidas con ese filtro.</flux:callout>
                    </flux:card>
                @endforelse
            </div>

            <div class="mt-8 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">Productos</flux:heading>
                        <flux:text>Pan, café en grano y otros artículos fuera del menú de bebidas.</flux:text>
                    </div>
                    <flux:badge color="zinc">{{ $visibleProducts->count() }} artículos</flux:badge>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 2xl:grid-cols-4">
                    @forelse ($visibleProducts as $product)
                        <flux:card wire:key="pos-product-{{ $product->id }}" class="overflow-hidden border-zinc-200 p-0 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-zinc-700">
                            <div class="aspect-[5/4] bg-gradient-to-br from-stone-100 via-white to-amber-100 dark:from-zinc-800 dark:via-zinc-900 dark:to-zinc-800">
                                @if ($product->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover" />
                                @else
                                    <div class="flex h-full items-center justify-center text-4xl font-semibold text-amber-700 dark:text-amber-400">
                                        {{ \Illuminate\Support\Str::of($product->name)->explode(' ')->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('') }}
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-4 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <flux:heading size="sm">{{ $product->name }}</flux:heading>
                                        <flux:text>{{ $product->description ?: 'Producto general' }}</flux:text>
                                    </div>
                                    <flux:badge color="amber">
                                        {{ $product->unit_type === 'piece' ? 'Pieza' : ($product->unit_type === 'gram' ? 'Gramo' : 'Kilo') }}
                                    </flux:badge>
                                </div>

                                <flux:button type="button" size="sm" variant="primary" class="w-full justify-between" wire:click="addProduct({{ $product->id }})">
                                    <span>Agregar</span>
                                    <span>${{ number_format($product->base_price, 2) }}</span>
                                </flux:button>
                            </div>
                        </flux:card>
                    @empty
                        <flux:card class="sm:col-span-2 2xl:col-span-3">
                            <flux:callout icon="information-circle" color="sky">No encontramos productos con ese filtro.</flux:callout>
                        </flux:card>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <flux:card class="flex min-h-0 flex-col overflow-hidden border-zinc-200 shadow-sm dark:border-zinc-700 xl:h-full">
        @if ($selectedCustomer)
            <flux:heading class="mb-2">{{ $selectedCustomer->name }}</flux:heading>
        @endif

        <div class="min-h-0 flex-1 overflow-hidden">
            <flux:tab.group class="flex h-full min-h-0 flex-col">
                <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                    <flux:tabs variant="segmented" size="sm" class="w-full">
                        <flux:tab name="customer" selected icon="user">Cliente</flux:tab>
                        <flux:tab name="order" icon="shopping-bag">Orden</flux:tab>
                        <flux:tab name="payment" icon="credit-card">Pago</flux:tab>
                    </flux:tabs>
                </div>

                <flux:tab.panel name="customer" selected class="min-h-0 flex-1 overflow-y-auto pr-1 pt-5">
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/70">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="font-medium">Cliente</div>
                                @if ($selectedCustomer)
                                    <flux:button type="button" variant="ghost" size="sm" wire:click="clearCustomer">Limpiar</flux:button>
                                @endif
                            </div>

                            @if ($selectedCustomer)
                                <div class="rounded-xl bg-white px-4 py-3 dark:bg-zinc-800">
                                    <div class="font-medium">{{ $selectedCustomer->name }}</div>
                                    <div class="text-sm text-zinc-500">{{ $selectedCustomer->reward_tier->label() }} · Saldo ${{ number_format($selectedCustomer->reward_balance, 2) }}</div>
                                </div>
                            @else
                                <div class="rounded-xl bg-white px-4 py-3 text-sm text-zinc-500 dark:bg-zinc-800">
                                    Venta para público general.
                                </div>
                            @endif

                            <flux:input wire:model.live.debounce.300ms="customer_search" label="Buscar cliente" placeholder="Nombre, teléfono o correo" />

                            @if ($customerResults->isNotEmpty())
                                <div class="grid gap-2">
                                    @foreach ($customerResults as $customer)
                                        <flux:button type="button" variant="{{ $customer_id === $customer->id ? 'primary' : 'ghost' }}" class="justify-between" wire:click="selectCustomer({{ $customer->id }})">
                                            <span>{{ $customer->name }}</span>
                                            <span class="text-xs text-zinc-500">{{ $customer->phone ?: 'Sin teléfono' }}</span>
                                        </flux:button>
                                    @endforeach
                                </div>
                            @endif

                            <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto_auto]">
                                <flux:input wire:model="qr_uuid" label="Escanear QR" placeholder="UUID del cliente" />
                                <div class="flex items-end">
                                    <x-qr-scanner-button field="qr_uuid" label="Cámara" />
                                </div>
                                <div class="flex items-end">
                                    <flux:button type="button" variant="primary" wire:click="assignCustomerByQr">Buscar QR</flux:button>
                                </div>
                            </div>
                        </div>
                    </div>
                </flux:tab.panel>

                <flux:tab.panel name="order" class="min-h-0 flex-1 overflow-y-auto pr-1 pt-5">
                    <div class="space-y-4">
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900/70">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium">Carrito activo</div>
                                    <div class="text-sm text-zinc-500">{{ collect($cart)->sum('quantity') }} piezas · {{ count($cart) }} líneas</div>
                                </div>
                                <flux:badge color="amber">{{ count($cart) }} items</flux:badge>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @forelse ($cart as $index => $item)
                                <div wire:key="pos-cart-item-{{ $index }}" class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-medium">{{ $item['item_name'] }}</div>
                                            <div class="text-sm text-zinc-500">{{ $item['size_label'] }} · ${{ number_format($this->cartItemUnitPrice($item), 2) }}</div>
                                        </div>
                                        <flux:button type="button" variant="ghost" size="sm" wire:click="removeItem({{ $index }})">Quitar</flux:button>
                                    </div>

                                    @if (($item['beverage_id'] ?? null) && ! empty($customizationGroups))
                                        <div class="mt-4 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/70">
                                            <div class="mb-2 text-sm font-medium text-zinc-600 dark:text-zinc-300">Extras</div>
                                            <div class="space-y-3">
                                                @foreach ($customizationGroups as $typeName => $options)
                                                    <div>
                                                        <div class="mb-2 text-xs uppercase tracking-wide text-zinc-500">{{ $typeName }}</div>
                                                        <div class="grid gap-2">
                                                            @foreach ($options as $option)
                                                                <flux:checkbox
                                                                    wire:model.live="cart.{{ $index }}.customization_option_ids"
                                                                    value="{{ $option['id'] }}"
                                                                    :label="$option['name'].' (+$'.number_format($option['price'], 2).')'"
                                                                />
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                                <flux:input wire:model.live="cart.{{ $index }}.special_customization_name" label="Extra especial" placeholder="Sin espuma, jarabe especial..." />
                                                <flux:input wire:model.live="cart.{{ $index }}.special_customization_price" label="Precio extra" type="number" step="0.01" min="0" />
                                            </div>
                                        </div>
                                    @elseif ($item['product_id'] ?? null)
                                        <div class="mt-4 rounded-xl bg-zinc-50 px-3 py-2 text-sm text-zinc-500 dark:bg-zinc-800/70">
                                            Este producto no usa extras de bebida. Ajusta la cantidad según piezas, gramos o kilos.
                                        </div>
                                    @endif

                                    <div class="mt-4 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <flux:button type="button" variant="ghost" size="sm" wire:click="decreaseQuantity({{ $index }})">-</flux:button>
                                            <div class="min-w-8 text-center font-medium">{{ $item['quantity'] }}</div>
                                            <flux:button type="button" variant="ghost" size="sm" wire:click="increaseQuantity({{ $index }})">+</flux:button>
                                        </div>

                                        <div class="font-semibold text-zinc-900 dark:text-white">
                                            ${{ number_format($this->cartItemLineTotal($item), 2) }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <flux:callout icon="shopping-bag" color="sky">Agrega bebidas desde la cuadrícula para comenzar.</flux:callout>
                            @endforelse
                        </div>

                        @error('cart')
                            <flux:callout icon="exclamation-triangle" color="rose">{{ $message }}</flux:callout>
                        @enderror
                    </div>
                </flux:tab.panel>

                <flux:tab.panel name="payment" class="min-h-0 flex-1 overflow-y-auto pr-1 pt-5">
                    <div class="space-y-4">
                        <div class="space-y-3">
                            <flux:radio.group wire:model="payment_method" label="Método de pago">
                                @foreach ($paymentMethods as $method)
                                    <flux:radio value="{{ $method->value }}" label="{{ $method->label() }}" />
                                @endforeach
                            </flux:radio.group>
                        </div>

                        <div class="grid gap-3">
                            <flux:input wire:model.live.debounce.200ms="discount_total" label="Descuento" type="number" step="0.01" min="0" />
                            <flux:input wire:model.live.debounce.200ms="discount_concept" label="Motivo del descuento" />
                            <flux:input wire:model.live.debounce.200ms="reward_redeemed_total" label="Saldo a favor usado" type="number" step="0.01" min="0" />
                            <flux:textarea wire:model.live.debounce.200ms="notes" label="Notas para la venta" rows="2" />
                        </div>
                    </div>
                </flux:tab.panel>
            </flux:tab.group>
        </div>

        <div class="mt-4 border-t border-zinc-200 bg-white pt-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="rounded-2xl bg-orange-50 p-4 dark:bg-orange-500/10">
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">Subtotal</span>
                        <span>${{ number_format($this->subtotal, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">Descuento</span>
                        <span>${{ number_format($discount_total, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">Saldo usado</span>
                        <span>${{ number_format($reward_redeemed_total, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-orange-100 pt-3 text-2xl font-semibold dark:border-orange-500/20">
                        <span>Total</span>
                        <span class="text-orange-600">${{ number_format($this->total, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <flux:button type="button" variant="ghost" class="w-full" wire:click="clearCart">Vaciar</flux:button>
                <flux:button type="button" variant="primary" class="w-full" wire:click="save">Cobrar</flux:button>
            </div>

            <div class="mt-3 text-center text-xs text-zinc-500">
                El cobro registra la venta inmediatamente en la sucursal activa.
            </div>
        </div>
    </flux:card>
</div>
