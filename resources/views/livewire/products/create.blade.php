<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $product ? 'Editar producto' : 'Nuevo producto' }}</flux:heading>
            <flux:text>Registra artículos como pan, bolsas de café o mercancía complementaria.</flux:text>
        </div>

        <flux:button :href="route('dashboard.products.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-4">
        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="name" label="Nombre" />
            <flux:textarea wire:model="description" label="Descripción" rows="4" />
            <flux:radio.group wire:model="unit_type" label="Tipo de unidad">
                <flux:radio value="piece" label="Pieza" />
                <flux:radio value="gram" label="Gramo" />
                <flux:radio value="kilo" label="Kilo" />
            </flux:radio.group>
            <flux:input wire:model="base_price" label="Precio base" type="number" step="0.01" min="0" />
            <flux:file-upload wire:model="image" label="Imagen" accept="image/*">
                <flux:file-upload.dropzone inline heading="Selecciona una imagen" text="PNG o JPG de hasta 3 MB" />
            </flux:file-upload>
            @if ($image)
                <img src="{{ $image->temporaryUrl() }}" alt="Vista previa del producto" class="h-24 w-24 rounded-2xl object-cover" />
            @elseif ($product?->image_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-24 w-24 rounded-2xl object-cover" />
            @endif
            <flux:field variant="inline">
                <flux:label>Activo</flux:label>
                <flux:switch wire:model.live="is_active" />
                <flux:error name="is_active" />
            </flux:field>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">{{ $product ? 'Actualizar producto' : 'Guardar producto' }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>
