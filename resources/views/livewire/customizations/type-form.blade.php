<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $customizationType ? 'Editar tipo' : 'Nuevo tipo' }}</flux:heading>
            <flux:text>Crea la familia de personalización antes de capturar sus variantes.</flux:text>
        </div>

        <flux:button :href="route('dashboard.customizations.types.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-6">
        <flux:tabs variant="segmented" class="w-auto!" size="sm">
            <flux:tab wire:click="$set('activeTab', 'general')" :data-current="$activeTab === 'general'" icon="sparkles">General</flux:tab>
            <flux:tab wire:click="$set('activeTab', 'beverages')" :data-current="$activeTab === 'beverages'" icon="beaker">Bebidas relacionadas</flux:tab>
        </flux:tabs>

        @if ($activeTab === 'general')
            <form wire:submit="save" class="space-y-4">
                <flux:input wire:model="type_name" label="Nombre del tipo" />
                <flux:radio.group wire:model="selection_mode" label="Modo de selección">
                    <flux:radio value="single" label="Una opción por bebida" />
                    <flux:radio value="multiple" label="Varias opciones por bebida" />
                </flux:radio.group>
                <flux:file-upload wire:model="type_image" label="Imagen del tipo" accept="image/*">
                    <flux:file-upload.dropzone inline heading="Selecciona una imagen" text="PNG o JPG de hasta 3 MB" />
                </flux:file-upload>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:button type="button" variant="ghost" wire:click="generateImage" wire:loading.attr="disabled" wire:target="generateImage">
                        <span wire:loading.remove wire:target="generateImage">{{ $customizationType?->image_path ? 'Regenerar imagen con IA' : 'Generar imagen con IA' }}</span>
                        <span wire:loading.inline-flex wire:target="generateImage" class="items-center gap-2">
                            <span class="size-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                            Generando imagen...
                        </span>
                    </flux:button>
                    <flux:text size="sm">Usa el nombre del tipo para conseguir una propuesta nueva.</flux:text>
                </div>
                @if ($type_image)
                    <img src="{{ $type_image->temporaryUrl() }}" alt="Vista previa del tipo" class="h-24 w-24 rounded-2xl object-cover" />
                @elseif ($customizationType?->image_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($customizationType->image_path) }}" alt="{{ $customizationType->name }}" class="h-24 w-24 rounded-2xl object-cover" />
                @endif
                <flux:field variant="inline">
                    <flux:label>Activo</flux:label>
                    <flux:switch wire:model.live="type_is_active" />
                    <flux:error name="type_is_active" />
                </flux:field>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">{{ $customizationType ? 'Actualizar tipo' : 'Guardar tipo' }}</flux:button>
                </div>
            </form>
        @endif

        @if ($activeTab === 'beverages')
            <div class="space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <flux:heading size="sm">Bebidas vinculadas</flux:heading>
                        <flux:text>Aquí puedes quitar la relación de este tipo con bebidas que ya lo usan.</flux:text>
                    </div>

                    @if ($selected_beverage_ids !== [])
                        <flux:button type="button" variant="danger" size="sm" wire:click="removeSelectedBeverages" icon="x-circle">
                            Deseleccionar {{ count($selected_beverage_ids) }}
                        </flux:button>
                    @endif
                </div>

                @if ($customizationType === null)
                    <flux:callout color="sky" icon="information-circle">
                        Guarda primero el tipo para administrar sus bebidas relacionadas.
                    </flux:callout>
                @elseif ($relatedBeverages->isEmpty())
                    <flux:callout color="zinc" icon="beaker">
                        Este tipo todavía no está vinculado a ninguna bebida.
                    </flux:callout>
                @else
                    <div class="space-y-3">
                        @foreach ($relatedBeverages as $beverage)
                            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="flex items-start gap-3">
                                    <flux:checkbox wire:model.live="selected_beverage_ids" value="{{ $beverage->id }}" />
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-white">{{ $beverage->name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $beverage->category?->name ?? 'Sin categoría' }}</div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($beverage->customizationOptions as $option)
                                                <flux:badge color="zinc">{{ $option->name }}</flux:badge>
                                            @endforeach
                                        </div>
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
