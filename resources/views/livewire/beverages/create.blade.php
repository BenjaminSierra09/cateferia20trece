<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $beverage ? 'Editar bebida' : 'Nueva bebida' }}</flux:heading>
            <flux:text>{{ $beverage ? 'Actualiza la configuración principal y comercial de la bebida.' : 'Registra una bebida con categoría y su matriz de precios por tamaño.' }}</flux:text>
        </div>

        <flux:button :href="route('dashboard.beverages.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:tabs variant="segmented" class="w-auto!" size="sm">
                <flux:tab wire:click="$set('activeTab', 'general')" :data-current="$activeTab === 'general'" icon="beaker">General</flux:tab>
                <flux:tab wire:click="$set('activeTab', 'customizations')" :data-current="$activeTab === 'customizations'" icon="sparkles">Personalizaciones</flux:tab>
                <flux:tab wire:click="$set('activeTab', 'pricing')" :data-current="$activeTab === 'pricing'" icon="currency-dollar">Precios</flux:tab>
            </flux:tabs>

            <div class="flex flex-wrap items-center gap-2">
                <flux:badge color="violet">{{ count($selected_customization_option_ids) }} opciones</flux:badge>
                <flux:badge color="sky">{{ collect($size_pricing)->where('enabled', true)->count() }} tamaños activos</flux:badge>
            </div>
        </div>

        <form wire:submit="save" class="space-y-6">
            @if ($activeTab === 'general')
                <div class="grid gap-4">
                    <flux:input wire:model="name" label="Nombre" />
                    <flux:textarea wire:model="description" label="Descripción" rows="4" />
                    <div class="space-y-2">
                        <flux:file-upload wire:model="image" label="Imagen" accept="image/*">
                            <flux:file-upload.dropzone inline heading="Selecciona una imagen" text="PNG o JPG de hasta 3 MB" />
                        </flux:file-upload>
                        <div class="flex flex-wrap items-center gap-3">
                            <flux:button type="button" variant="ghost" wire:click="generateImage" wire:loading.attr="disabled" wire:target="generateImage">
                                <span wire:loading.remove wire:target="generateImage">{{ $beverage?->image_path ? 'Regenerar imagen con IA' : 'Generar imagen con IA' }}</span>
                                <span wire:loading.inline-flex wire:target="generateImage" class="items-center gap-2">
                                    <span class="size-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                                    Generando imagen...
                                </span>
                            </flux:button>
                            <flux:text size="sm">Usa el nombre y la descripción actual para generar una nueva imagen.</flux:text>
                        </div>
                        @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" alt="Vista previa de bebida" class="h-32 w-32 rounded-2xl object-cover" />
                        @elseif ($beverage?->image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($beverage->image_path) }}" alt="{{ $beverage->name }}" class="h-32 w-32 rounded-2xl object-cover" />
                        @endif
                    </div>
                    <flux:select wire:model="beverage_category_id" label="Categoría">
                        <option value="">Selecciona una categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:field variant="inline">
                        <flux:label>Activa</flux:label>
                        <flux:switch wire:model.live="is_active" />
                        <flux:error name="is_active" />
                    </flux:field>
                </div>
            @endif

            @if ($activeTab === 'customizations')
                <div class="space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <flux:heading size="sm">Personalizaciones vinculadas</flux:heading>
                            <flux:text>Selecciona qué opciones estarán disponibles para esta bebida en la app y en ventas.</flux:text>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button type="button" variant="ghost" size="sm" wire:click="collapseAllCustomizationTypes" icon="chevron-up-down">
                                Comprimir todas
                            </flux:button>
                            <flux:button type="button" variant="ghost" size="sm" wire:click="expandAllCustomizationTypes" icon="arrows-pointing-out">
                                Expandir todas
                            </flux:button>
                            <flux:button type="button" variant="ghost" size="sm" wire:click="selectAllCustomizationOptions" icon="check-circle">
                                Seleccionar todas
                            </flux:button>
                            <flux:button type="button" variant="ghost" size="sm" wire:click="clearCustomizationOptions" icon="x-circle">
                                Deseleccionar todas
                            </flux:button>
                        </div>
                    </div>

                    @if ($customizationTypes->isEmpty())
                        <flux:callout color="sky" icon="information-circle">
                            Primero crea tipos y opciones de personalización para poder vincularlas a la bebida.
                        </flux:callout>
                    @else
                        <div wire:sort="sortCustomizationType" class="space-y-3">
                            @foreach ($customizationTypes as $type)
                                @php
                                    $isCollapsed = in_array($type->id, array_map('intval', $collapsed_customization_type_ids), true);
                                    $selectedCount = $type->options->pluck('id')->intersect(array_map('intval', $selected_customization_option_ids))->count();
                                    $isTemperatureType = $type->slug === \App\Support\BeverageTemperatureCustomization::TypeSlug;
                                @endphp
                                <div wire:key="customization-type-{{ $type->id }}" wire:sort:item="{{ $type->id }}" class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex min-w-0 items-start gap-3">
                                            <div @if (! $isTemperatureType) wire:sort:handle @endif>
                                                <flux:icon.bars-3 class="mt-0.5 size-5 shrink-0 cursor-grab text-zinc-400" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2 font-medium text-zinc-900 dark:text-white">
                                                    {{ $type->name }}
                                                    @if ($isTemperatureType)
                                                        <flux:badge color="orange">Primera</flux:badge>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-zinc-500">
                                                    {{ $type->selection_mode === 'single' ? 'Una opción a la vez' : 'Se permiten múltiples opciones' }}
                                                </div>
                                            </div>
                                        </div>

                                        <div wire:sort:ignore class="flex flex-wrap items-center gap-2">
                                            <flux:field variant="inline" class="rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-900">
                                                <flux:label class="text-xs">Abierta</flux:label>
                                                <flux:switch wire:model.live="customization_type_settings.{{ $type->id }}.is_open_by_default" />
                                                <flux:error name="customization_type_settings.{{ $type->id }}.is_open_by_default" />
                                            </flux:field>
                                            <flux:badge color="zinc">{{ $selectedCount }}/{{ $type->options->count() }} opciones</flux:badge>
                                            <flux:button type="button" variant="ghost" size="sm" :icon="$isCollapsed ? 'chevron-down' : 'chevron-up'" wire:click="toggleCustomizationTypeOptions({{ $type->id }})">
                                                {{ $isCollapsed ? 'Ver opciones' : 'Ocultar' }}
                                            </flux:button>
                                        </div>
                                    </div>

                                    <div wire:sort:ignore class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                        <div class="text-xs text-zinc-500">
                                            Orden {{ ($customization_type_settings[$type->id]['sort_order'] ?? 0) + 1 }}
                                        </div>

                                        <div class="flex items-center gap-2">
                                            @if ($type->options->isNotEmpty() && ! $isTemperatureType)
                                                <flux:button type="button" variant="ghost" size="sm" wire:click="selectAllCustomizationOptions({{ $type->id }})">
                                                    Todas
                                                </flux:button>
                                                <flux:button type="button" variant="ghost" size="sm" wire:click="clearCustomizationOptions({{ $type->id }})">
                                                    Ninguna
                                                </flux:button>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($type->options->isEmpty())
                                        <div wire:sort:ignore class="mt-3">
                                            <flux:text size="sm" class="text-zinc-500">No hay opciones disponibles en este tipo.</flux:text>
                                        </div>
                                    @elseif ($isCollapsed)
                                        <div wire:sort:ignore class="mt-3 rounded-xl bg-zinc-50 px-3 py-2 text-sm text-zinc-500 dark:bg-zinc-900">
                                            {{ $selectedCount }} seleccionadas de {{ $type->options->count() }} opciones
                                        </div>
                                    @else
                                        <div wire:sort:ignore class="mt-3 grid gap-3">
                                            @foreach ($type->options as $option)
                                                @php
                                                    $isSelected = in_array($option->id, array_map('intval', $selected_customization_option_ids), true);
                                                @endphp
                                                <div class="flex items-start justify-between gap-3 rounded-xl border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                                    <label class="flex min-w-0 flex-1 items-start gap-3">
                                                        <flux:checkbox wire:model.live="selected_customization_option_ids" value="{{ $option->id }}" :disabled="$isTemperatureType" />
                                                        <div class="min-w-0">
                                                            <div class="font-medium text-zinc-900 dark:text-white">{{ $option->name }}</div>
                                                            <div class="text-sm text-zinc-500">${{ number_format($option->price, 2) }}</div>
                                                        </div>
                                                    </label>

                                                    <flux:field variant="inline" class="shrink-0">
                                                        <flux:label class="text-xs text-zinc-500">Default</flux:label>
                                                        <flux:checkbox wire:model.live="default_customization_option_ids" value="{{ $option->id }}" :disabled="! $isSelected" />
                                                        <flux:error name="default_customization_option_ids" />
                                                    </flux:field>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if ($activeTab === 'pricing')
                <div class="space-y-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <flux:heading size="sm">Precios por tamaño</flux:heading>
                            <flux:text>Activa solo los tamaños que realmente venderás y define precio y disponibilidad por sucursal.</flux:text>
                        </div>
                        <flux:badge color="sky" class="rounded-full px-3 py-1">{{ count($size_pricing) }} tamaños disponibles</flux:badge>
                    </div>

                    @error('size_pricing')
                        <flux:callout color="rose" icon="exclamation-triangle">{{ $message }}</flux:callout>
                    @enderror

                    <div class="space-y-4">
                        @foreach ($size_pricing as $index => $pricing)
                            <div wire:key="size-pricing-{{ $pricing['size_id'] }}" class="rounded-3xl border border-zinc-200 bg-white/80 p-5 shadow-sm shadow-zinc-900/5 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/60 dark:shadow-none">
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <div class="text-base font-semibold text-zinc-900 dark:text-white">{{ $pricing['size_name'] }}</div>
                                        <div class="mt-1 text-sm text-zinc-500">{{ $pricing['capacity_label'] }}</div>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-[220px_auto] md:items-end">
                                        <flux:input
                                            wire:model="size_pricing.{{ $index }}.price"
                                            label="Precio general"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            :disabled="! $pricing['enabled']"
                                            class="min-w-0"
                                            placeholder="0.00"
                                        />

                                        <flux:field variant="inline" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950/40">
                                            <flux:label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Disponible</flux:label>
                                            <flux:switch wire:model.live="size_pricing.{{ $index }}.enabled" class="shrink-0" />
                                            <flux:error name="size_pricing.{{ $index }}.enabled" />
                                        </flux:field>
                                    </div>
                                </div>

                                @error("size_pricing.$index.price")
                                    <div class="mt-3">
                                        <flux:text class="text-sm text-rose-600">{{ $message }}</flux:text>
                                    </div>
                                @enderror

                                @if ($pricing['enabled'])
                                    <div class="mt-5 space-y-3 rounded-3xl bg-zinc-50/90 p-4 ring-1 ring-inset ring-zinc-200/70 dark:bg-zinc-900/50 dark:ring-zinc-700/70">
                                        <div>
                                            <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Sucursales</div>
                                            <div class="text-xs text-zinc-500">Desactiva el tamaño donde no se venda. Si no completas un precio, esa sucursal usará el precio general.</div>
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-2">
                                            @foreach ($branches as $branch)
                                                @php
                                                    $branchAvailability = $pricing['branch_availability'][$branch->id] ?? $pricing['branch_availability'][(string) $branch->id] ?? true;
                                                @endphp
                                                <div class="space-y-3 rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950/40">
                                                    <flux:field variant="inline" class="justify-between">
                                                        <flux:label>{{ $branch->name }}</flux:label>
                                                        <flux:switch wire:model.live="size_pricing.{{ $index }}.branch_availability.{{ $branch->id }}" />
                                                        <flux:error name="size_pricing.{{ $index }}.branch_availability.{{ $branch->id }}" />
                                                    </flux:field>

                                                    <flux:input
                                                        wire:model="size_pricing.{{ $index }}.branch_prices.{{ $branch->id }}"
                                                        label="Precio en sucursal"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        :disabled="! $branchAvailability"
                                                        class="min-w-0"
                                                        placeholder="Usar precio general"
                                                    />
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">{{ $beverage ? 'Actualizar bebida' : 'Guardar bebida' }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>
