<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $customizationOption ? 'Editar opción' : 'Nueva opción' }}</flux:heading>
            <flux:text>Agrega la variante concreta y el precio que aporta al ticket.</flux:text>
        </div>

        <flux:button :href="route('dashboard.customizations.options.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-6">
        <flux:tabs variant="segmented" class="w-auto!" size="sm">
            <flux:tab wire:click="$set('activeTab', 'general')" :data-current="$activeTab === 'general'" icon="sparkles">General</flux:tab>
            <flux:tab wire:click="$set('activeTab', 'beverages')" :data-current="$activeTab === 'beverages'" icon="beaker">Bebidas relacionadas</flux:tab>
        </flux:tabs>

        @if ($activeTab === 'general')
            <form wire:submit="save" class="space-y-4">
                <flux:select wire:model="customization_type_id" label="Tipo">
                    <option value="">Selecciona un tipo</option>
                    @foreach ($customizationTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="option_name" label="Nombre de la opción" />
                <flux:input wire:model="option_price" label="Precio adicional" type="number" step="0.01" min="0" />
                <flux:file-upload wire:model="option_image" label="Imagen de la opción" accept="image/*">
                    <flux:file-upload.dropzone inline heading="Selecciona una imagen" text="PNG o JPG de hasta 3 MB" />
                </flux:file-upload>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:button type="button" variant="ghost" wire:click="generateImage" wire:loading.attr="disabled" wire:target="generateImage">
                        <span wire:loading.remove wire:target="generateImage">{{ $customizationOption?->image_path ? 'Regenerar imagen con IA' : 'Generar imagen con IA' }}</span>
                        <span wire:loading.inline-flex wire:target="generateImage" class="items-center gap-2">
                            <span class="size-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                            Generando imagen...
                        </span>
                    </flux:button>
                    <flux:text size="sm">La generación toma el nombre de la opción y su contexto actual.</flux:text>
                </div>
                @if ($option_image)
                    <img src="{{ $option_image->temporaryUrl() }}" alt="Vista previa de la opción" class="h-24 w-24 rounded-2xl object-cover" />
                @elseif ($customizationOption?->image_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($customizationOption->image_path) }}" alt="{{ $customizationOption->name }}" class="h-24 w-24 rounded-2xl object-cover" />
                @endif
                <flux:field variant="inline">
                    <flux:label>Disponible</flux:label>
                    <flux:switch wire:model.live="is_available" />
                    <flux:error name="is_available" />
                </flux:field>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">{{ $customizationOption ? 'Actualizar opción' : 'Guardar opción' }}</flux:button>
                </div>
            </form>
        @endif

        @if ($activeTab === 'beverages')
            <div class="space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <flux:heading size="sm">Bebidas vinculadas</flux:heading>
                        <flux:text>Selecciona las bebidas donde esta opción debe estar disponible.</flux:text>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button type="button" variant="ghost" size="sm" wire:click="selectAllBeverages" icon="check-circle">
                            Seleccionar todas
                        </flux:button>
                        <flux:button type="button" variant="ghost" size="sm" wire:click="clearAllBeverages" icon="x-circle">
                            Deseleccionar todas
                        </flux:button>
                        @if ($selected_beverage_ids !== [])
                            <flux:button type="button" variant="danger" size="sm" wire:click="removeSelectedBeverages" icon="x-circle">
                                Deseleccionar {{ count($selected_beverage_ids) }}
                            </flux:button>
                        @endif
                    </div>
                </div>

                @if ($customizationOption === null)
                    <flux:callout color="sky" icon="information-circle">
                        Guarda primero la opción para administrar sus bebidas relacionadas.
                    </flux:callout>
                @elseif ($beverages->isEmpty())
                    <flux:callout color="zinc" icon="beaker">
                        Todavía no hay bebidas registradas.
                    </flux:callout>
                @else
                    <div class="space-y-3">
                        @foreach ($beverages as $beverage)
                            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="flex items-start gap-3">
                                    <flux:checkbox wire:model.live="selected_beverage_ids" value="{{ $beverage->id }}" />
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-white">{{ $beverage->name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $beverage->category?->name ?? 'Sin categoría' }}</div>
                                    </div>
                                </div>

                                <flux:button type="button" variant="ghost" wire:click="removeBeverage({{ $beverage->id }})" icon="x-mark">
                                    Quitar relación
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </flux:card>
</div>
