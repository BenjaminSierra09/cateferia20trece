<div class="space-y-6">
    <flux:card class="space-y-4 overflow-hidden">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <flux:heading size="xl">Colaboradores</flux:heading>
                <flux:text>Gestiona al equipo activo de la cafetería.</flux:text>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <flux:select wire:model.live="perPage" size="sm" class="w-full sm:w-24">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </flux:select>

                <flux:button
                    :href="route('dashboard.team.create')"
                    variant="primary"
                    wire:navigate
                    class="w-full sm:w-auto"
                >
                    Nuevo colaborador
                </flux:button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <flux:table :paginate="$users" class="min-w-[760px]">
                <flux:table.columns>
                    <flux:table.column>Nombre</flux:table.column>
                    <flux:table.column>Usuario</flux:table.column>
                    <flux:table.column>Rol</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column>Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($users as $user)
                        <flux:table.row wire:key="team-user-row-{{ $user->id }}">
                            <flux:table.cell>
                                <div class="min-w-0">
                                    <div class="truncate font-medium">
                                        {{ $user->name }}
                                    </div>
                                    <div class="truncate text-sm text-zinc-500">
                                        {{ $user->email }}
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="block max-w-40 truncate">
                                    {{ $user->username }}
                                </span>
                            </flux:table.cell>

                            <flux:table.cell>
                                {{ $user->role->label() }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge color="{{ $user->is_active ? 'emerald' : 'zinc' }}">
                                    {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                    <flux:button
                                        :href="route('dashboard.team.edit', $user)"
                                        variant="ghost"
                                        size="sm"
                                        wire:navigate
                                        class="w-full sm:w-auto"
                                    >
                                        Editar
                                    </flux:button>

                                    <flux:button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        wire:click="toggleActive({{ $user->id }})"
                                        class="w-full sm:w-auto"
                                    >
                                        {{ $user->is_active ? 'Eliminar' : 'Reactivar' }}
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>