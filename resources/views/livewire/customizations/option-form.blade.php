<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $customizationOption ? 'Editar opción' : 'Nueva opción' }}</flux:heading>
            <flux:text>Agrega la variante concreta y el precio que aporta al ticket.</flux:text>
        </div>

        <flux:button :href="route('dashboard.customizations.options.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-4">
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
    </flux:card>
</div>
