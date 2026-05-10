<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $customizationType ? 'Editar tipo' : 'Nuevo tipo' }}</flux:heading>
            <flux:text>Crea la familia de personalización antes de capturar sus variantes.</flux:text>
        </div>

        <flux:button :href="route('dashboard.customizations.types.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-4">
        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="type_name" label="Nombre del tipo" />
            <flux:radio.group wire:model="selection_mode" label="Modo de selección">
                <flux:radio value="single" label="Una opción por bebida" />
                <flux:radio value="multiple" label="Varias opciones por bebida" />
            </flux:radio.group>
            <flux:file-upload wire:model="type_image" label="Imagen del tipo" accept="image/*">
                <flux:file-upload.dropzone inline heading="Selecciona una imagen" text="PNG o JPG de hasta 3 MB" />
            </flux:file-upload>
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
    </flux:card>
</div>
