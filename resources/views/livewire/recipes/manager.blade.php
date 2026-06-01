<div class="space-y-6">
    <div>
        <flux:heading size="xl">Recetas</flux:heading>
        <flux:text class="mt-2">
            Define qué insumos del inventario consume cada bebida (por tamaño), producto o personalización. El stock se descuenta solo al vender.
        </flux:text>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar..." class="max-w-sm" />

    <flux:tab.group>
        <flux:tabs wire:model.live="tab">
            <flux:tab name="bebidas">Bebidas</flux:tab>
            <flux:tab name="productos">Productos</flux:tab>
            <flux:tab name="personalizaciones">Personalizaciones</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="bebidas">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Bebida</flux:table.column>
                    <flux:table.column>Receta</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->beverages as $beverage)
                        <flux:table.row wire:key="rec-bev-{{ $beverage->id }}">
                            <flux:table.cell variant="strong">{{ $beverage->name }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($beverage->recipe_lines_count > 0)
                                    <flux:badge size="sm" color="emerald">{{ $beverage->recipe_lines_count }} línea(s)</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">Sin receta</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end">
                                    <flux:button size="xs" variant="ghost" icon="pencil-square" :href="route('dashboard.recipes.beverages.edit', $beverage)" wire:navigate>Editar receta</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="3"><flux:text class="py-4">No hay bebidas.</flux:text></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:tab.panel>

        <flux:tab.panel name="productos">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Producto</flux:table.column>
                    <flux:table.column>Receta</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->products as $product)
                        <flux:table.row wire:key="rec-prod-{{ $product->id }}">
                            <flux:table.cell variant="strong">{{ $product->name }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($product->recipe_lines_count > 0)
                                    <flux:badge size="sm" color="emerald">{{ $product->recipe_lines_count }} línea(s)</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">Sin receta</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end">
                                    <flux:button size="xs" variant="ghost" icon="pencil-square" :href="route('dashboard.recipes.products.edit', $product)" wire:navigate>Editar receta</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="3"><flux:text class="py-4">No hay productos.</flux:text></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:tab.panel>

        <flux:tab.panel name="personalizaciones">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Categoría</flux:table.column>
                    <flux:table.column>Receta</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->types as $type)
                        <flux:table.row wire:key="rec-type-{{ $type->id }}">
                            <flux:table.cell variant="strong">{{ $type->name }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($type->recipe_lines_count > 0)
                                    <flux:badge size="sm" color="emerald">{{ $type->recipe_lines_count }} línea(s)</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">Sin receta</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end">
                                    <flux:button size="xs" variant="ghost" icon="pencil-square" :href="route('dashboard.recipes.customizations.edit', $type)" wire:navigate>Editar receta</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="3"><flux:text class="py-4">No hay categorías de personalización.</flux:text></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:tab.panel>
    </flux:tab.group>
</div>
