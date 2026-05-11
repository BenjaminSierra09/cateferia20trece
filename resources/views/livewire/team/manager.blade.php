<div class="space-y-6">
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="xl">Colaboradores</flux:heading>
                    <flux:text>Gestiona al equipo activo de la cafetería.</flux:text>
                </div>

                <div class="flex items-center gap-3">
                    <flux:select wire:model.live="perPage" size="sm" class="w-24">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </flux:select>
                    <flux:button :href="route('dashboard.team.create')" variant="primary" wire:navigate>Nuevo colaborador</flux:button>
                </div>
            </div>

            <flux:table :paginate="$users">
                <flux:table.columns>
                    <flux:table.column>Nombre</flux:table.column>
                    <flux:table.column>Usuario</flux:table.column>
                    <flux:table.column>Rol</flux:table.column>
                    <flux:table.column>Sucursal</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column>Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                @foreach ($users as $user)
                    <flux:table.row wire:key="team-user-row-{{ $user->id }}">
                        <flux:table.cell>
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-sm text-zinc-500">{{ $user->email }}</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $user->username }}</flux:table.cell>
                        <flux:table.cell>{{ $user->role->label() }}</flux:table.cell>
                        <flux:table.cell>{{ $user->branch?->name ?? 'Sin sucursal principal' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $user->is_active ? 'emerald' : 'zinc' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                <flux:button :href="route('dashboard.team.edit', $user)" variant="ghost" size="sm" wire:navigate>Editar</flux:button>
                                <flux:button type="button" variant="danger" size="sm" wire:click="toggleActive({{ $user->id }})">
                                    {{ $user->is_active ? 'Eliminar' : 'Reactivar' }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
