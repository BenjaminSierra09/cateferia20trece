<div class="mx-auto max-w-2xl space-y-6">
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">Confirmación diaria</flux:heading>
                <flux:text>Selecciona la sucursal desde la que trabajarás hoy.</flux:text>
            </div>

            @if ($currentSession)
                <flux:callout icon="check-circle" color="emerald">
                    Sesión abierta en <strong>{{ $currentSession->branch?->name }}</strong> desde las {{ $currentSession->clock_in_at?->format('H:i') }}.
                </flux:callout>
            @endif

            <form wire:submit="start" class="space-y-4">
                <flux:select wire:model="branch_id" :label="__('Sucursal')">
                    <option value="">Selecciona una sucursal</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }} @if($branch->city) · {{ $branch->city }} @endif</option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="notes" :label="__('Notas')" rows="3" />

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">Confirmar sucursal</flux:button>

                    @if ($currentSession)
                        <flux:button type="button" variant="ghost" wire:click="close">Cerrar sesión de hoy</flux:button>
                    @endif
                </div>
            </form>
        </flux:card>
    </div>
