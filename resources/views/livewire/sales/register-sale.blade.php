<div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading>Venta manual</flux:heading>
                <flux:text>Crea una venta detallada cuando necesites ajustes especiales.</flux:text>
            </div>

            <div class="flex items-center gap-3">
                <flux:button :href="route('dashboard.sales.index')" variant="ghost" wire:navigate>Historial</flux:button>
                <flux:button :href="route('dashboard.sales.pos')" variant="primary" wire:navigate>Ir al POS</flux:button>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <flux:card class="space-y-6">
            <div>
                <flux:text>
                    @if ($currentSession)
                        Registrando desde {{ $currentSession->branch?->name }}.
                    @else
                        Debes confirmar tu sucursal primero.
                    @endif
                </flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-3">
                    <flux:input wire:model.live.debounce.300ms="customer_search" label="Buscar cliente" placeholder="Nombre, teléfono o correo" />

                    @if ($selectedCustomer)
                        <div class="rounded-xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium">{{ $selectedCustomer->name }}</div>
                                    <div class="text-sm text-zinc-500">{{ $selectedCustomer->phone ?: 'Sin teléfono' }}</div>
                                </div>
                                <flux:button type="button" variant="ghost" size="sm" wire:click="clearCustomer">Limpiar</flux:button>
                            </div>
                        </div>
                    @endif

                    @if ($customerResults->isNotEmpty())
                        <div class="grid gap-2">
                            @foreach ($customerResults as $customer)
                                <flux:button type="button" variant="{{ $customer_id === $customer->id ? 'primary' : 'ghost' }}" class="justify-between" wire:click="selectCustomer({{ $customer->id }})">
                                    <span>{{ $customer->name }}</span>
                                    <span class="text-xs text-zinc-500">{{ $customer->email ?: ($customer->phone ?: 'Sin dato') }}</span>
                                </flux:button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="space-y-3">
                    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto_auto]">
                        <flux:input wire:model="qr_uuid" label="QR del cliente" placeholder="UUID del cliente" />
                        <div class="flex items-end">
                            <x-qr-scanner-button field="qr_uuid" label="Cámara" />
                        </div>
                        <div class="flex items-end">
                            <flux:button type="button" variant="primary" wire:click="assignCustomerByQr">Buscar QR</flux:button>
                        </div>
                    </div>

                    <flux:radio.group wire:model="payment_method" label="Método de pago">
                        @foreach ($paymentMethods as $method)
                            <flux:radio value="{{ $method->value }}" label="{{ $method->label() }}" />
                        @endforeach
                    </flux:radio.group>
                </div>
            </div>

            <div class="space-y-4">
                @foreach ($items as $index => $item)
                    <div wire:key="sale-item-{{ $index }}" class="space-y-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="grid gap-4 md:grid-cols-2">
                            <flux:select wire:model="items.{{ $index }}.beverage_id" label="Bebida">
                                <option value="">Producto manual</option>
                                @foreach ($beverages as $beverage)
                                    <option value="{{ $beverage->id }}">{{ $beverage->name }}</option>
                                @endforeach
                            </flux:select>

                            <flux:select wire:model="items.{{ $index }}.product_id" label="Producto">
                                <option value="">Sin producto</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} · {{ $product->unit_type === 'gram' ? 'por gramo' : 'por pieza' }}</option>
                                @endforeach
                            </flux:select>

                            <flux:select wire:model="items.{{ $index }}.size_id" label="Tamaño">
                                <option value="">Sin tamaño</option>
                                @foreach ($sizes as $size)
                                    <option value="{{ $size->id }}">{{ $size->name }} · {{ $size->capacity_label }}</option>
                                @endforeach
                            </flux:select>

                            <flux:input wire:model="items.{{ $index }}.item_name" label="Nombre manual" />
                            <flux:input wire:model="items.{{ $index }}.quantity" label="Cantidad" type="number" min="1" />
                            <flux:input wire:model="items.{{ $index }}.unit_price" label="Precio manual" type="number" step="0.01" min="0" />
                            <flux:input wire:model="items.{{ $index }}.special_instructions" label="Indicaciones" />
                            <div class="space-y-3 md:col-span-2">
                                <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Extras</div>
                                @php
                                    $selectedProduct = $products->firstWhere('id', $item['product_id']);
                                    $customizationGroups = $customizationOptions->groupBy(fn ($option) => $option->type?->name ?? 'Sin tipo');
                                @endphp

                                @if ($item['beverage_id'] && $customizationGroups->isNotEmpty())
                                    <div class="grid gap-3">
                                        @foreach ($customizationGroups as $typeName => $options)
                                            <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/70">
                                                <div class="mb-2 text-xs uppercase tracking-wide text-zinc-500">{{ $typeName }}</div>
                                                <div class="grid gap-2">
                                                    @foreach ($options as $option)
                                                        <flux:checkbox
                                                            wire:model.live="items.{{ $index }}.customization_option_ids"
                                                            value="{{ $option->id }}"
                                                            :label="$option->name.' (+$'.number_format($option->price, 2).')'"
                                                        />
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif ($selectedProduct)
                                    <flux:callout icon="information-circle" color="sky">
                                        Este producto usa precio {{ $selectedProduct->unit_type === 'gram' ? 'por gramo' : 'por pieza' }} y no suma extras de bebida.
                                    </flux:callout>
                                @else
                                    <flux:callout icon="information-circle" color="sky">Selecciona una bebida para aplicar extras globales o un producto para vender pan, café y otros artículos.</flux:callout>
                                @endif
                            </div>
                            <flux:input wire:model="items.{{ $index }}.special_customization_name" label="Extra especial" />
                            <flux:input wire:model="items.{{ $index }}.special_customization_price" label="Precio extra especial" type="number" step="0.01" min="0" />
                        </div>

                        <flux:button type="button" variant="ghost" wire:click="removeItem({{ $index }})">Quitar producto</flux:button>
                    </div>
                @endforeach
            </div>

            <div class="flex gap-3">
                <flux:button type="button" variant="ghost" wire:click="addItem">Agregar producto</flux:button>
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading>Resumen</flux:heading>
            <flux:input wire:model="discount_total" label="Descuento" type="number" step="0.01" min="0" />
            <flux:input wire:model="discount_concept" label="Concepto de descuento" />
            <flux:input wire:model="reward_redeemed_total" label="Saldo a favor usado" type="number" step="0.01" min="0" />
            <flux:textarea wire:model="notes" label="Notas generales" rows="4" />
            <flux:button type="button" variant="primary" wire:click="save">Registrar venta</flux:button>

            <flux:separator />

            <flux:heading size="sm">Ventas recientes</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Cliente</flux:table.column>
                    <flux:table.column>Sucursal</flux:table.column>
                    <flux:table.column>Fecha</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                @foreach ($recentSales as $sale)
                    <flux:table.row wire:key="recent-sale-row-{{ $sale->id }}">
                        <flux:table.cell>{{ $sale->customer?->name ?? 'Público general' }}</flux:table.cell>
                        <flux:table.cell>{{ $sale->branch?->name ?: 'Sin sucursal' }}</flux:table.cell>
                        <flux:table.cell>{{ $sale->sold_at?->format('d/m H:i') }}</flux:table.cell>
                        <flux:table.cell>${{ number_format($sale->total, 2) }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
        </div>
</div>
