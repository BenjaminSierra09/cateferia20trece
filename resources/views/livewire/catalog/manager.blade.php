<div class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-2">
            <flux:card class="space-y-4">
                <flux:heading>Alta rápida</flux:heading>

                <form wire:submit="createBranch" class="grid gap-3 md:grid-cols-2">
                    <flux:input wire:model="branch_name" label="Sucursal" />
                    <flux:input wire:model="branch_city" label="Ciudad" />
                    <flux:button type="submit" variant="primary" class="md:col-span-2">Crear sucursal</flux:button>
                </form>

                <flux:separator />

                <form wire:submit="createCategory" class="grid gap-3">
                    <flux:input wire:model="category_name" label="Categoría" />
                    <flux:button type="submit" variant="primary">Crear categoría</flux:button>
                </form>

                <flux:separator />

                <form wire:submit="createSize" class="grid gap-3 md:grid-cols-2">
                    <flux:input wire:model="size_name" label="Tamaño" />
                    <flux:input wire:model="size_capacity_label" label="Capacidad" placeholder="12 oz" />
                    <flux:button type="submit" variant="primary" class="md:col-span-2">Crear tamaño</flux:button>
                </form>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading>Personalizaciones y bebidas</flux:heading>

                <form wire:submit="createCustomizationType" class="grid gap-3">
                    <flux:input wire:model="customization_type_name" label="Tipo de personalización" placeholder="Tipo de leche" />
                    <flux:button type="submit" variant="primary">Crear tipo</flux:button>
                </form>

                <flux:separator />

                <form wire:submit="createCustomizationOption" class="grid gap-3 md:grid-cols-3">
                    <flux:select wire:model="customization_type_id" label="Tipo">
                        <option value="">Selecciona</option>
                        @foreach ($customizationTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="customization_option_name" label="Opción" />
                    <flux:input wire:model="customization_option_price" label="Precio" type="number" step="0.01" min="0" />
                    <flux:button type="submit" variant="primary" class="md:col-span-3">Crear opción</flux:button>
                </form>

                <flux:separator />

                <form wire:submit="createBeverage" class="grid gap-3 md:grid-cols-2">
                    <flux:input wire:model="beverage_name" label="Bebida" />
                    <flux:select wire:model="beverage_category_id" label="Categoría">
                        <option value="">Selecciona</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="beverage_size_id" label="Tamaño inicial">
                        <option value="">Selecciona</option>
                        @foreach ($sizes as $size)
                            <option value="{{ $size->id }}">{{ $size->name }} · {{ $size->capacity_label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="beverage_price" label="Precio" type="number" step="0.01" min="0" />
                    <flux:button type="submit" variant="primary" class="md:col-span-2">Crear bebida</flux:button>
                </form>
            </flux:card>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <flux:card class="space-y-3">
                <flux:heading>Sucursales</flux:heading>
                @foreach ($branches as $branch)
                    <div class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">{{ $branch->name }} @if($branch->city) · {{ $branch->city }} @endif</div>
                @endforeach
            </flux:card>

            <flux:card class="space-y-3">
                <flux:heading>Tamaños</flux:heading>
                @foreach ($sizes as $size)
                    <div class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">{{ $size->name }} · {{ $size->capacity_label }}</div>
                @endforeach
            </flux:card>

            <flux:card class="space-y-3">
                <flux:heading>Bebidas</flux:heading>
                @foreach ($beverages as $beverage)
                    <div class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                        <div class="font-medium">{{ $beverage->name }}</div>
                        <div class="text-sm text-zinc-500">{{ $beverage->category?->name }}</div>
                    </div>
                @endforeach
            </flux:card>
        </div>
    </div>
