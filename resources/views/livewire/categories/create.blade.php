<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $category ? 'Editar categoría' : 'Nueva categoría' }}</flux:heading>
            <flux:text>{{ $category ? 'Actualiza la categoría del menú.' : 'Crea una categoría para organizar el menú.' }}</flux:text>
        </div>

        <flux:button :href="route('dashboard.categories.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-4">
        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="name" label="Nombre" />
            <flux:textarea wire:model="description" label="Descripción" rows="5" />
            <div class="space-y-2">
                <flux:file-upload wire:model="image" label="Imagen" accept="image/*">
                    <flux:file-upload.dropzone inline heading="Selecciona una imagen" text="PNG o JPG de hasta 3 MB" />
                </flux:file-upload>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:button type="button" variant="ghost" wire:click="generateImage" wire:loading.attr="disabled" wire:target="generateImage">
                        <span wire:loading.remove wire:target="generateImage">{{ $category?->image_path ? 'Regenerar imagen con IA' : 'Generar imagen con IA' }}</span>
                        <span wire:loading.inline-flex wire:target="generateImage" class="items-center gap-2">
                            <span class="size-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                            Generando imagen...
                        </span>
                    </flux:button>
                    <flux:text size="sm">La IA toma la categoría y descripción actual para regenerarla.</flux:text>
                </div>
                @if ($image)
                    <img src="{{ $image->temporaryUrl() }}" alt="Vista previa de categoría" class="h-28 w-28 rounded-2xl object-cover" />
                @elseif ($category?->image_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($category->image_path) }}" alt="{{ $category->name }}" class="h-28 w-28 rounded-2xl object-cover" />
                @endif
            </div>
            <flux:field variant="inline">
                <flux:label>Activa</flux:label>
                <flux:switch wire:model.live="is_active" />
                <flux:error name="is_active" />
            </flux:field>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">{{ $category ? 'Actualizar categoría' : 'Guardar categoría' }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>
