<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Símbolos aztecas</flux:heading>
            <flux:text>Administra la lectura, gustos y recomendaciones que consume Android.</flux:text>
        </div>

        <flux:badge color="amber">{{ $symbols->count() }} símbolos</flux:badge>
    </div>

    <flux:card class="space-y-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Símbolo</flux:table.column>
                <flux:table.column>Perfil de gustos</flux:table.column>
                <flux:table.column>Recomendaciones</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($symbols as $symbol)
                    <flux:table.row wire:key="aztec-symbol-row-{{ $symbol->id }}">
                        <flux:table.cell>
                            <div class="space-y-1">
                                <div class="font-medium">{{ $symbol->sort_order }}. {{ $symbol->name }}</div>
                                <div class="text-sm text-zinc-500">{{ $symbol->spanish_name ?: 'Sin traducción' }}</div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="max-w-md truncate">{{ $symbol->taste_profile ?: 'Sin perfil' }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @forelse (($symbol->recommended_items ?? []) as $item)
                                    <flux:badge color="zinc">{{ $item }}</flux:badge>
                                @empty
                                    <flux:badge color="amber">Sin recomendaciones</flux:badge>
                                @endforelse
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $symbol->is_active ? 'emerald' : 'zinc' }}">
                                {{ $symbol->is_active ? 'Activo' : 'Oculto' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                <flux:button :href="route('dashboard.aztec-symbols.edit', $symbol)" variant="ghost" size="sm" wire:navigate>
                                    Editar
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:callout color="sky" icon="information-circle">
                                Ejecuta el seeder para cargar los 20 símbolos aztecas.
                            </flux:callout>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
