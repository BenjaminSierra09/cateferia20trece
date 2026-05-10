<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $beverage ? 'Editar bebida' : 'Nueva bebida' }}</flux:heading>
            <flux:text>{{ $beverage ? 'Actualiza la configuración principal y comercial de la bebida.' : 'Registra una bebida con categoría y su matriz de precios por tamaño.' }}</flux:text>
        </div>

        <flux:button :href="route('dashboard.beverages.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-4">
        <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="name" label="Nombre" class="md:col-span-2" />
            <flux:textarea wire:model="description" label="Descripción" rows="4" class="md:col-span-2" />
            <div class="space-y-2 md:col-span-2">
                <flux:file-upload wire:model="image" label="Imagen" accept="image/*">
                    <flux:file-upload.dropzone inline heading="Selecciona una imagen" text="PNG o JPG de hasta 3 MB" />
                </flux:file-upload>
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
            <flux:field variant="inline" class="self-end">
                <flux:label>Activa</flux:label>
                <flux:switch wire:model.live="is_active" />
                <flux:error name="is_active" />
            </flux:field>
            <div class="space-y-4 md:col-span-2">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <flux:heading size="sm">Precios por tamaño</flux:heading>
                        <flux:text>Activa los tamaños que venderá esta bebida y captura el precio general. Si una sucursal cobra distinto, puedes sobrescribirlo aquí mismo.</flux:text>
                    </div>
                    <flux:badge color="sky">{{ count($size_pricing) }} tamaños disponibles</flux:badge>
                </div>

                @error('size_pricing')
                    <flux:callout color="rose" icon="exclamation-triangle">{{ $message }}</flux:callout>
                @enderror

                <div class="space-y-4">
                    @foreach ($size_pricing as $index => $pricing)
                        <div wire:key="size-pricing-{{ $pricing['size_id'] }}" class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                                <div>
                                    <div class="text-base font-medium text-zinc-900 dark:text-white">{{ $pricing['size_name'] }}</div>
                                    <div class="text-sm text-zinc-500">{{ $pricing['capacity_label'] }}</div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-[180px_auto] md:items-end">
                                    <flux:input
                                        wire:model="size_pricing.{{ $index }}.price"
                                        label="Precio general"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :disabled="! $pricing['enabled']"
                                        placeholder="0.00"
                                    />

                                    <flux:field variant="inline">
                                        <flux:label>Disponible</flux:label>
                                        <flux:switch wire:model.live="size_pricing.{{ $index }}.enabled" />
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
                                <div class="mt-4 space-y-3 rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900/60">
                                    <div>
                                        <div class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Precios por sucursal</div>
                                        <div class="text-xs text-zinc-500">Opcional. Si lo dejas vacío, esa sucursal usará el precio general del tamaño.</div>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
                                        @foreach ($branches as $branch)
                                            <flux:input
                                                wire:model="size_pricing.{{ $index }}.branch_prices.{{ $branch->id }}"
                                                :label="$branch->name"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                placeholder="Usar precio general"
                                            />
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="md:col-span-2 flex justify-end">
                <flux:button type="submit" variant="primary">{{ $beverage ? 'Actualizar bebida' : 'Guardar bebida' }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>
