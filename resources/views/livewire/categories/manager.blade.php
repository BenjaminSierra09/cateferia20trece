<div class="space-y-6">
    <flux:card class="space-y-4">
        <div>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="xl">Categorías</flux:heading>
                    <flux:text>Organiza el menú por familias de bebidas.</flux:text>
                </div>

                <div class="flex items-center gap-3">
                    <flux:select wire:model.live="perPage" size="sm" class="w-24">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </flux:select>
                    <flux:button :href="route('dashboard.categories.create')" variant="primary" icon="plus" wire:navigate>Nueva categoría</flux:button>
                </div>
            </div>
        </div>

        <flux:table :paginate="$categories">
            <flux:table.columns>
                <flux:table.column>Categoría</flux:table.column>
                <flux:table.column>Descripción</flux:table.column>
                <flux:table.column>Bebidas</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($categories as $category)
                    <flux:table.row wire:key="category-row-{{ $category->id }}">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                @if ($category->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($category->image_path) }}" alt="{{ $category->name }}" class="h-12 w-12 rounded-xl object-cover" />
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-500/10">
                                        <flux:icon.tag class="size-5" />
                                    </div>
                                @endif
                                <div>{{ $category->name }}</div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $category->description ?: 'Sin descripción' }}</flux:table.cell>
                        <flux:table.cell>{{ $category->beverages_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $category->is_active ? 'emerald' : 'zinc' }}">
                                {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                <flux:button :href="route('dashboard.categories.edit', $category)" variant="ghost" size="sm" wire:navigate>Editar</flux:button>
                                <flux:button type="button" variant="danger" size="sm" wire:click="toggleActive({{ $category->id }})">
                                    {{ $category->is_active ? 'Eliminar' : 'Reactivar' }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:callout icon="information-circle" color="sky">Todavía no hay categorías registradas.</flux:callout>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
