<div class="space-y-4" wire:poll.10s>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl">WhatsApp</flux:heading>
            <flux:text>Consulta las conversaciones recientes y responde desde el Dashboard.</flux:text>
        </div>

        <flux:badge color="emerald" icon="check-circle">API oficial de Meta</flux:badge>
    </div>

    <div class="grid h-[calc(100dvh-11rem)] min-h-[38rem] overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 lg:grid-cols-[22rem_minmax(0,1fr)]">
        <aside class="{{ $selectedConversationId ? 'hidden lg:flex' : 'flex' }} min-h-0 flex-col border-zinc-200 dark:border-zinc-700 lg:flex lg:border-e">
            <div class="space-y-3 border-b border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between gap-3">
                    <flux:heading size="lg">Conversaciones</flux:heading>
                    <flux:badge color="zinc">{{ $this->conversations->count() }}</flux:badge>
                </div>

                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Buscar nombre o teléfono"
                    autocomplete="off"
                />
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                @forelse ($this->conversations as $conversation)
                    <button
                        type="button"
                        wire:key="conversation-{{ $conversation->id }}"
                        wire:click="selectConversation({{ $conversation->id }})"
                        class="flex w-full items-center gap-3 border-b border-zinc-100 px-4 py-3 text-start transition hover:bg-zinc-50 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-emerald-600 dark:border-zinc-800 dark:hover:bg-zinc-800/70 {{ $selectedConversationId === $conversation->id ? 'bg-emerald-50 dark:bg-emerald-950/30' : '' }}"
                    >
                        <flux:avatar :name="$conversation->displayName()" color="auto" size="md" />

                        <span class="min-w-0 flex-1">
                            <span class="flex items-baseline justify-between gap-2">
                                <span class="truncate text-sm font-semibold text-zinc-900 dark:text-white">
                                    {{ $conversation->displayName() }}
                                </span>
                                @if ($conversation->last_message_at)
                                    <span class="shrink-0 text-xs text-zinc-500">
                                        {{ $conversation->last_message_at->diffForHumans(short: true) }}
                                    </span>
                                @endif
                            </span>

                            <span class="mt-1 flex items-center gap-1.5">
                                @if ($conversation->latestMessage?->direction === \App\Enums\WhatsAppMessageDirection::Outbound)
                                    <flux:icon.arrow-turn-up-right class="size-3.5 shrink-0 text-zinc-400" />
                                @endif
                                <span class="truncate text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $conversation->latestMessage?->body ?? 'Mensaje sin contenido' }}
                                </span>
                            </span>
                        </span>
                    </button>
                @empty
                    <div class="flex h-full flex-col items-center justify-center gap-3 px-8 py-12 text-center">
                        <span class="grid size-12 place-items-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
                            <flux:icon.chat-bubble-left-right class="size-6" />
                        </span>
                        <div>
                            <flux:heading size="sm">Sin conversaciones</flux:heading>
                            <flux:text size="sm">
                                {{ trim($search) !== '' ? 'No encontramos coincidencias.' : 'Los mensajes nuevos aparecerán aquí.' }}
                            </flux:text>
                        </div>
                    </div>
                @endforelse
            </div>
        </aside>

        <section class="{{ $selectedConversationId ? 'flex' : 'hidden lg:flex' }} min-h-0 min-w-0 flex-col bg-zinc-50/70 dark:bg-zinc-950/40">
            @if ($this->selectedConversation)
                @php($selectedConversation = $this->selectedConversation)

                <header class="flex min-h-18 items-center gap-3 border-b border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:button
                        wire:click="closeConversation"
                        variant="ghost"
                        size="sm"
                        icon="arrow-left"
                        aria-label="Volver a conversaciones"
                        class="lg:hidden"
                    />
                    <flux:avatar :name="$selectedConversation->displayName()" color="auto" size="md" />

                    <div class="min-w-0 flex-1">
                        <flux:heading size="sm" class="truncate">{{ $selectedConversation->displayName() }}</flux:heading>
                        <flux:text size="sm" class="truncate">+{{ $selectedConversation->phone }}</flux:text>
                    </div>

                    @if ($selectedConversation->hasOpenCustomerServiceWindow())
                        <flux:badge color="emerald" icon="clock" class="max-sm:hidden">Puedes responder</flux:badge>
                    @else
                        <flux:badge color="amber" icon="clock" class="max-sm:hidden">Ventana cerrada</flux:badge>
                    @endif
                </header>

                <div
                    x-data="{ scrollToEnd() { $nextTick(() => $refs.messages.scrollTop = $refs.messages.scrollHeight) } }"
                    x-init="scrollToEnd()"
                    x-on:whatsapp-message-sent.window="scrollToEnd()"
                    class="min-h-0 flex-1"
                >
                    <div x-ref="messages" class="flex h-full flex-col gap-2 overflow-y-auto px-4 py-5 sm:px-6">
                        @if ($selectedConversation->messages->count() >= $messageLimit && $messageLimit < 250)
                            <div class="mb-3 text-center">
                                <flux:button wire:click="loadEarlier" variant="ghost" size="xs" icon="arrow-up">
                                    Cargar mensajes anteriores
                                </flux:button>
                            </div>
                        @endif

                        @foreach ($selectedConversation->messages as $message)
                            @php($isOutbound = $message->direction === \App\Enums\WhatsAppMessageDirection::Outbound)

                            <div wire:key="message-{{ $message->id }}" class="flex {{ $isOutbound ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[85%] sm:max-w-[72%]">
                                    <div class="rounded-2xl px-3.5 py-2.5 shadow-sm {{ $isOutbound ? 'rounded-br-sm bg-emerald-600 text-white' : 'rounded-bl-sm border border-zinc-200 bg-white text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100' }}">
                                        <p class="whitespace-pre-wrap break-words text-sm leading-relaxed">{{ $message->body ?? 'Mensaje sin contenido' }}</p>

                                        <div class="mt-1 flex items-center justify-end gap-1 text-[0.68rem] {{ $isOutbound ? 'text-emerald-100' : 'text-zinc-400' }}">
                                            @if ($isOutbound && $message->sentBy)
                                                <span class="me-1">{{ $message->sentBy->name }}</span>
                                            @endif

                                            <time datetime="{{ $message->sent_at?->toIso8601String() }}">
                                                {{ $message->sent_at?->format('H:i') }}
                                            </time>

                                            @if ($isOutbound)
                                                @if ($message->status === \App\Enums\WhatsAppMessageStatus::Failed)
                                                    <flux:icon.exclamation-circle class="size-3.5" aria-label="Error al enviar" />
                                                @elseif ($message->status === \App\Enums\WhatsAppMessageStatus::Read)
                                                    <flux:icon.check-badge class="size-3.5" aria-label="Leído" />
                                                @elseif ($message->status === \App\Enums\WhatsAppMessageStatus::Delivered)
                                                    <flux:icon.check-circle class="size-3.5" aria-label="Entregado" />
                                                @else
                                                    <flux:icon.check class="size-3.5" aria-label="Enviado" />
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <footer class="border-t border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900 sm:p-4">
                    @if (! $selectedConversation->hasOpenCustomerServiceWindow())
                        <flux:callout icon="clock" color="amber" class="mb-3">
                            La ventana de atención de 24 horas terminó. Para iniciar otra conversación necesitas una plantilla aprobada por Meta.
                        </flux:callout>
                    @endif

                    <form wire:submit="send">
                        <flux:composer
                            wire:model="reply"
                            name="reply"
                            submit="enter"
                            rows="1"
                            max-rows="5"
                            placeholder="Escribe un mensaje"
                            :disabled="! $selectedConversation->hasOpenCustomerServiceWindow()"
                        >
                            <x-slot name="actionsTrailing">
                                <flux:button
                                    type="submit"
                                    variant="primary"
                                    size="sm"
                                    icon="paper-airplane"
                                    aria-label="Enviar mensaje"
                                    wire:loading.attr="disabled"
                                    wire:target="send"
                                    :disabled="! $selectedConversation->hasOpenCustomerServiceWindow()"
                                />
                            </x-slot>
                        </flux:composer>
                        <flux:error name="reply" />
                    </form>
                </footer>
            @else
                <div class="flex h-full flex-col items-center justify-center gap-4 p-8 text-center">
                    <span class="grid size-16 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        <flux:icon.chat-bubble-left-right class="size-8" />
                    </span>
                    <div class="max-w-sm">
                        <flux:heading>WhatsApp de Café 20Trece</flux:heading>
                        <flux:text class="mt-1">Selecciona una conversación para consultar sus mensajes y responder.</flux:text>
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>
