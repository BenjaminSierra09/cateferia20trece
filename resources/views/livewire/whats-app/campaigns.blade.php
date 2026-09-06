<div class="space-y-6" wire:poll.10s>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl">Campañas de WhatsApp</flux:heading>
            <flux:text>Envía promociones con plantillas aprobadas por Meta y conserva el seguimiento de cada contacto.</flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:badge color="emerald" icon="check-circle">API oficial de Meta</flux:badge>
            <flux:button :href="route('dashboard.whatsapp.index')" variant="ghost" icon="chat-bubble-left-right" wire:navigate>
                Ir a conversaciones
            </flux:button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <flux:card class="space-y-2">
            <div class="flex items-center justify-between gap-3">
                <flux:text class="text-sm">Contactos autorizados</flux:text>
                <span class="grid size-9 place-items-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                    <flux:icon.user-group class="size-5" />
                </span>
            </div>
            <div class="text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->eligibleCustomerCount) }}</div>
            <flux:text class="text-xs">Sólo números con permiso vigente.</flux:text>
        </flux:card>

        <flux:card class="space-y-2">
            <div class="flex items-center justify-between gap-3">
                <flux:text class="text-sm">Campañas activas</flux:text>
                <span class="grid size-9 place-items-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                    <flux:icon.paper-airplane class="size-5" />
                </span>
            </div>
            <div class="text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->activeCampaignCount) }}</div>
            <flux:text class="text-xs">En cola o enviándose ahora.</flux:text>
        </flux:card>

        <flux:card class="space-y-2 sm:col-span-2">
            <div class="flex items-start gap-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                    <flux:icon.shield-check class="size-5" />
                </span>
                <div>
                    <flux:heading size="sm">Protección de consentimiento</flux:heading>
                    <flux:text class="mt-1 text-sm">
                        El permiso se comprueba al crear la campaña y otra vez justo antes de enviar. Las bajas se excluyen automáticamente.
                    </flux:text>
                </div>
            </div>
        </flux:card>
    </div>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(24rem,0.95fr)]">
        <flux:card class="space-y-5">
            <div>
                <flux:heading size="lg">Nueva campaña</flux:heading>
                <flux:text>Captura exactamente el nombre y los parámetros de la plantilla aprobada en Meta.</flux:text>
            </div>

            <form wire:submit="createCampaign" class="space-y-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="name" label="Nombre interno" placeholder="Promoción de otoño" />
                    <flux:input wire:model="template_name" label="Plantilla de Meta" placeholder="promocion_otono" />
                    <flux:input wire:model="template_language" label="Idioma" placeholder="es_MX" />
                    <div class="flex items-end">
                        <flux:callout color="sky" icon="information-circle" class="w-full text-sm">
                            La plantilla debe estar aprobada en la categoría Marketing.
                        </flux:callout>
                    </div>
                </div>

                <flux:textarea
                    wire:model="message_preview"
                    label="Vista previa del mensaje"
                    description="Se mostrará en el historial del Dashboard. Puedes usar las variables disponibles; el contenido real lo toma Meta de la plantilla aprobada."
                    rows="5"
                    placeholder="Hola, tenemos una promoción especial para ti..."
                />

                <flux:textarea
                    wire:model="template_parameter_lines"
                    label="Parámetros del cuerpo"
                    description="Uno por línea y en el mismo orden que la plantilla. Puedes combinar texto con las variables disponibles."
                    rows="5"
                    placeholder="@{{first_name}}&#10;2x1 en bebidas&#10;@{{reward_balance}}"
                />

                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Support\WhatsAppCampaignParameterRenderer::SUPPORTED_TOKENS as $token)
                        <flux:badge color="zinc">{{ $token }}</flux:badge>
                    @endforeach
                </div>

                <flux:radio.group wire:model.live="audience" label="Destinatarios">
                    <flux:radio value="all" label="Todos los contactos autorizados" />
                    <flux:radio value="selected" label="Elegir contactos" />
                </flux:radio.group>
                <flux:error name="audience" />

                @if ($audience === 'selected')
                    <div class="max-h-72 space-y-2 overflow-y-auto rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        @forelse ($this->eligibleCustomers as $customer)
                            <label wire:key="campaign-customer-{{ $customer->id }}" class="flex cursor-pointer items-center gap-3 rounded-xl px-2 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                <flux:checkbox wire:model="selected_customer_ids" value="{{ $customer->id }}" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $customer->name }}</span>
                                    <span class="block truncate text-xs text-zinc-500">{{ $customer->phone }}</span>
                                </span>
                            </label>
                        @empty
                            <flux:text class="py-6 text-center">Todavía no hay contactos autorizados.</flux:text>
                        @endforelse
                    </div>
                    <flux:error name="selected_customer_ids" />
                @endif

                <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                    <flux:checkbox
                        wire:model="consent_confirmed"
                        label="Confirmo que esta plantilla fue aprobada por Meta como Marketing y que el contenido corresponde al permiso otorgado por los contactos."
                    />
                    <flux:error name="consent_confirmed" />
                </div>

                <div class="flex justify-end">
                    <flux:button
                        type="submit"
                        variant="primary"
                        icon="paper-airplane"
                        wire:loading.attr="disabled"
                        wire:target="createCampaign"
                    >
                        Crear y poner en cola
                    </flux:button>
                </div>
            </form>
        </flux:card>

        <flux:card class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">Campañas recientes</flux:heading>
                    <flux:text>Selecciona una para consultar su entrega.</flux:text>
                </div>
                <flux:badge color="zinc">{{ $this->campaigns->count() }}</flux:badge>
            </div>

            <div class="max-h-[46rem] space-y-2 overflow-y-auto">
                @forelse ($this->campaigns as $campaign)
                    <button
                        type="button"
                        wire:key="campaign-{{ $campaign->id }}"
                        wire:click="selectCampaign({{ $campaign->id }})"
                        class="w-full rounded-2xl border p-4 text-start transition hover:border-emerald-300 hover:bg-emerald-50/40 focus-visible:outline-2 focus-visible:outline-emerald-600 dark:hover:border-emerald-800 dark:hover:bg-emerald-950/20 {{ $selectedCampaignId === $campaign->id ? 'border-emerald-400 bg-emerald-50/60 dark:border-emerald-700 dark:bg-emerald-950/30' : 'border-zinc-200 dark:border-zinc-700' }}"
                    >
                        <span class="flex items-start justify-between gap-3">
                            <span class="min-w-0">
                                <span class="block truncate font-semibold text-zinc-950 dark:text-white">{{ $campaign->name }}</span>
                                <span class="mt-1 block truncate text-xs text-zinc-500">{{ $campaign->template_name }} · {{ $campaign->created_at->format('d/m/Y H:i') }}</span>
                            </span>
                            <flux:badge :color="$campaign->status->color()">{{ $campaign->status->label() }}</flux:badge>
                        </span>

                        <span class="mt-3 grid grid-cols-3 gap-2 text-xs text-zinc-500">
                            <span><strong class="block text-base text-zinc-900 dark:text-zinc-100">{{ $campaign->successful_recipients_count }}</strong> enviados</span>
                            <span><strong class="block text-base text-zinc-900 dark:text-zinc-100">{{ $campaign->delivered_recipients_count }}</strong> entregados</span>
                            <span><strong class="block text-base text-zinc-900 dark:text-zinc-100">{{ $campaign->read_recipients_count }}</strong> leídos</span>
                        </span>
                    </button>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-700">
                        <flux:icon.paper-airplane class="mx-auto size-8 text-zinc-400" />
                        <flux:heading size="sm" class="mt-3">Sin campañas</flux:heading>
                        <flux:text class="mt-1 text-sm">La primera campaña aparecerá aquí.</flux:text>
                    </div>
                @endforelse
            </div>
        </flux:card>
    </div>

    @if ($this->selectedCampaign)
        @php($campaign = $this->selectedCampaign)

        <flux:card class="space-y-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="lg">{{ $campaign->name }}</flux:heading>
                        <flux:badge :color="$campaign->status->color()">{{ $campaign->status->label() }}</flux:badge>
                    </div>
                    <flux:text class="mt-1">
                        {{ $campaign->template_name }} · {{ $campaign->template_language }} · Creada por {{ $campaign->createdBy?->name ?? 'Sistema' }}
                    </flux:text>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if (($campaign->failed_recipients_count > 0 || ($campaign->status === \App\Enums\WhatsAppCampaignStatus::Failed && $campaign->pending_recipients_count > 0)) && in_array($campaign->status, [\App\Enums\WhatsAppCampaignStatus::Completed, \App\Enums\WhatsAppCampaignStatus::Failed], true))
                        <flux:button
                            wire:click="retryFailures({{ $campaign->id }})"
                            wire:confirm="Se pondrán en cola sólo los envíos que no comenzaron o que Meta rechazó de forma explícita. ¿Continuar?"
                            variant="ghost"
                            icon="arrow-path"
                            size="sm"
                        >
                            Reanudar o reintentar
                        </flux:button>
                    @endif

                    @if ($campaign->status->canCancel())
                        <flux:button
                            wire:click="cancelCampaign({{ $campaign->id }})"
                            wire:confirm="Se cancelarán los envíos que todavía no han comenzado. ¿Continuar?"
                            variant="danger"
                            icon="stop-circle"
                            size="sm"
                        >
                            Cancelar pendientes
                        </flux:button>
                    @endif
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                @foreach ([
                    ['label' => 'Contactos', 'value' => $campaign->recipients_count],
                    ['label' => 'Enviados', 'value' => $campaign->successful_recipients_count],
                    ['label' => 'Entregados', 'value' => $campaign->delivered_recipients_count],
                    ['label' => 'Leídos', 'value' => $campaign->read_recipients_count],
                    ['label' => 'Fallidos', 'value' => $campaign->failed_recipients_count],
                    ['label' => 'Por revisar', 'value' => $campaign->uncertain_recipients_count],
                ] as $metric)
                    <div class="rounded-2xl border border-zinc-200 p-3 dark:border-zinc-700">
                        <flux:text class="text-xs">{{ $metric['label'] }}</flux:text>
                        <div class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($metric['value']) }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.45fr)]">
                <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                    <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        <flux:heading size="sm">Destinatarios</flux:heading>
                    </div>

                    <div class="max-h-96 overflow-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Contacto</flux:table.column>
                                <flux:table.column>Estado</flux:table.column>
                                <flux:table.column class="max-md:hidden">Detalle</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($campaign->recipients as $recipient)
                                    <flux:table.row wire:key="campaign-recipient-{{ $recipient->id }}">
                                        <flux:table.cell>
                                            <div class="font-medium">{{ $recipient->name }}</div>
                                            <div class="text-xs text-zinc-500">+{{ $recipient->phone }}</div>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:badge :color="$recipient->status->color()">{{ $recipient->status->label() }}</flux:badge>
                                        </flux:table.cell>
                                        <flux:table.cell class="max-md:hidden">
                                            <span class="text-sm text-zinc-500">
                                                {{ $recipient->error_message ?: ($recipient->read_at?->format('d/m/Y H:i') ?? $recipient->delivered_at?->format('d/m/Y H:i') ?? $recipient->sent_at?->format('d/m/Y H:i') ?? 'Esperando') }}
                                            </span>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>

                    @if ($campaign->recipients_count > 100)
                        <div class="border-t border-zinc-200 px-4 py-3 text-xs text-zinc-500 dark:border-zinc-700">
                            Se muestran los primeros 100 de {{ number_format($campaign->recipients_count) }} destinatarios.
                        </div>
                    @endif
                </div>

                <div class="space-y-3 rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                    <flux:heading size="sm">Vista previa guardada</flux:heading>
                    <p class="whitespace-pre-wrap text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $campaign->message_preview }}</p>

                    @if ($campaign->template_parameters)
                        <div class="border-t border-zinc-200 pt-3 dark:border-zinc-700">
                            <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Parámetros</div>
                            <ol class="mt-2 list-decimal space-y-1 ps-5 text-sm text-zinc-600 dark:text-zinc-300">
                                @foreach ($campaign->template_parameters as $parameter)
                                    <li>{{ $parameter }}</li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>
    @endif
</div>
