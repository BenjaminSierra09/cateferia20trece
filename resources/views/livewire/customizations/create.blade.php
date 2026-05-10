<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Personalización</flux:heading>
            <flux:text>Administra el tipo y sus opciones desde una pantalla dedicada.</flux:text>
        </div>

        <flux:button :href="route('dashboard.customizations.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <flux:card class="space-y-4">
            <div>
                <flux:heading>Tipo</flux:heading>
                <flux:text>Ejemplo: leche, azúcar o temperatura.</flux:text>
            </div>

            <form wire:submit="createType" class="space-y-4">
                <flux:input wire:model="type_name" label="Nombre del tipo" />
                <flux:select wire:model="selection_mode" label="Modo de selección">
                    <option value="single">Una opción</option>
                    <option value="multiple">Múltiples opciones</option>
                </flux:select>
                <div class="space-y-2">
                    <flux:file-upload wire:model="type_image" label="Imagen del tipo" accept="image/*">
                        <flux:file-upload.dropzone inline heading="Selecciona una imagen" text="PNG o JPG de hasta 3 MB" />
                    </flux:file-upload>
                    @if ($type_image)
                        <img src="{{ $type_image->temporaryUrl() }}" alt="Vista previa del tipo" class="h-24 w-24 rounded-2xl object-cover" />
                    @elseif ($customizationType?->image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($customizationType->image_path) }}" alt="{{ $customizationType->name }}" class="h-24 w-24 rounded-2xl object-cover" />
                    @endif
                </div>
                <flux:field variant="inline">
                    <flux:label>Activo</flux:label>
                    <flux:switch wire:model.live="type_is_active" />
                    <flux:error name="type_is_active" />
                </flux:field>
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">{{ $customizationType ? 'Actualizar tipo' : 'Guardar tipo' }}</flux:button>
                </div>
            </form>
        </flux:card>

        <flux:card class="space-y-4">
            <div>
                <flux:heading>Opción</flux:heading>
                <flux:text>Asigna una variante con su precio adicional.</flux:text>
            </div>

            <form wire:submit="createOption" class="space-y-4">
                <flux:select wire:model="customization_type_id" label="Tipo">
                    <option value="">Selecciona un tipo</option>
                    @foreach ($customizationTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="option_name" label="Nombre de la opción" />
                <div class="space-y-2">
                    <flux:file-upload wire:model="option_image" label="Imagen de la opción" accept="image/*">
                        <flux:file-upload.dropzone inline heading="Selecciona una imagen" text="PNG o JPG de hasta 3 MB" />
                    </flux:file-upload>
                    @if ($option_image)
                        <img src="{{ $option_image->temporaryUrl() }}" alt="Vista previa de la opción" class="h-24 w-24 rounded-2xl object-cover" />
                    @elseif ($customizationOption?->image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($customizationOption->image_path) }}" alt="{{ $customizationOption->name }}" class="h-24 w-24 rounded-2xl object-cover" />
                    @endif
                </div>
                <flux:input wire:model="option_price" label="Precio adicional" type="number" step="0.01" min="0" />
                <flux:field variant="inline">
                    <flux:label>Disponible</flux:label>
                    <flux:switch wire:model.live="is_available" />
                    <flux:error name="is_available" />
                </flux:field>
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">{{ $customizationOption ? 'Actualizar opción' : 'Guardar opción' }}</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</div>
