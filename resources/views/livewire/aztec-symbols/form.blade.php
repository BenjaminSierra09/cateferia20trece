<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Editar {{ $aztecSymbol->name }}</flux:heading>
            <flux:text>Estos textos se enviarán por API para las recomendaciones del punto de venta.</flux:text>
        </div>

        <flux:button :href="route('dashboard.aztec-symbols.index')" variant="ghost" wire:navigate>
            Volver
        </flux:button>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="name" label="Nombre nahua" />
                <flux:input wire:model="spanishName" label="Nombre en español" />
                <flux:input wire:model="deity" label="Deidad asociada" />
                <flux:input wire:model="bodyArea" label="Área corporal" />
            </div>

            <flux:textarea wire:model="meaning" label="Significado" rows="2" />
            <flux:textarea wire:model="serviceDescription" label="Descripción de atención" rows="2" />
            <flux:textarea wire:model="customerGreeting" label="Frase sugerida para cliente" rows="2" />
            <flux:textarea wire:model="tasteProfile" label="Perfil de gustos" rows="2" />
            <flux:textarea wire:model="recommendedItemsText" label="Recomendaciones" description="Una recomendación por línea." rows="4" />

            <flux:field variant="inline">
                <flux:label>Visible en Android</flux:label>
                <flux:switch wire:model.live="isActive" />
                <flux:error name="isActive" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button :href="route('dashboard.aztec-symbols.index')" variant="ghost" wire:navigate>
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Guardar cambios</span>
                    <span wire:loading.inline wire:target="save">Guardando...</span>
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
