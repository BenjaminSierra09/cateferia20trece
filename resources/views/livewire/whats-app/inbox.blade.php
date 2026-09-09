<div class="{{ $standalone ? 'h-full' : 'space-y-4' }}" wire:poll.10s>
    @unless ($standalone)
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="xl">WhatsApp</flux:heading>
                <flux:text>Consulta las conversaciones recientes y responde desde el Dashboard.</flux:text>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button :href="route('dashboard.whatsapp.campaigns')" variant="ghost" icon="paper-airplane" wire:navigate>
                    Campañas
                </flux:button>
                <flux:badge color="emerald" icon="check-circle">API oficial de Meta</flux:badge>
            </div>
        </div>
    @endunless

    <div class="grid overflow-hidden border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 lg:grid-cols-[22rem_minmax(0,1fr)] {{ $standalone ? 'h-full border-0' : 'h-[calc(100dvh-11rem)] min-h-[38rem] rounded-2xl border shadow-sm' }}">
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
                                    {{ $conversation->latestMessage?->previewText() ?? 'Mensaje sin contenido' }}
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

                    <div class="flex shrink-0 items-center gap-2">
                        @if ($selectedConversation->hasOpenCustomerServiceWindow())
                            <flux:badge color="emerald" icon="clock" class="max-sm:hidden">Puedes responder</flux:badge>
                        @else
                            <flux:badge color="amber" icon="clock" class="max-sm:hidden">Ventana cerrada</flux:badge>
                        @endif

                        <flux:button
                            wire:click="toggleBot"
                            :variant="$selectedConversation->isBotPaused() ? 'primary' : 'ghost'"
                            size="sm"
                            :icon="$selectedConversation->isBotPaused() ? 'play-circle' : 'pause-circle'"
                            :aria-label="$selectedConversation->isBotPaused() ? 'Reactivar bot' : 'Pausar bot'"
                            wire:loading.attr="disabled"
                            wire:target="toggleBot"
                        >
                            <span class="max-sm:hidden">
                                {{ $selectedConversation->isBotPaused() ? 'Reactivar bot' : 'Pausar bot' }}
                            </span>
                        </flux:button>
                    </div>
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

                            <div wire:key="message-{{ $message->id }}" class="group flex {{ $isOutbound ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[85%] sm:max-w-[72%]">
                                    <div class="rounded-2xl px-3.5 py-2.5 shadow-sm {{ $isOutbound ? 'rounded-br-sm bg-emerald-600 text-white' : 'rounded-bl-sm border border-zinc-200 bg-white text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100' }}">
                                        @if ($message->type === 'image')
                                            @if ($message->media_path)
                                                <a
                                                    href="{{ route('dashboard.whatsapp.media', $message) }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="mb-2 block overflow-hidden rounded-xl"
                                                >
                                                    <img
                                                        src="{{ route('dashboard.whatsapp.media', $message) }}"
                                                        alt="Foto enviada por WhatsApp"
                                                        class="max-h-80 w-full object-cover"
                                                        loading="lazy"
                                                    />
                                                </a>
                                            @else
                                                <div class="mb-1 flex items-center gap-2 text-sm">
                                                    <flux:icon.photo class="size-4" />
                                                    <span>Imagen recibida</span>
                                                </div>
                                            @endif
                                        @endif

                                        @if ($message->type === 'audio')
                                            @if ($message->media_path)
                                                <audio
                                                    controls
                                                    preload="metadata"
                                                    class="mb-1 block h-10 w-72 max-w-full"
                                                    aria-label="Audio de WhatsApp"
                                                >
                                                    <source
                                                        src="{{ route('dashboard.whatsapp.media', $message) }}"
                                                        type="{{ $message->media_mime_type ?? 'application/octet-stream' }}"
                                                    />
                                                    Tu navegador no puede reproducir este audio.
                                                </audio>
                                            @else
                                                <div class="mb-1 flex items-center gap-2 text-sm">
                                                    <flux:icon.speaker-wave class="size-4" />
                                                    <span>Audio no disponible</span>
                                                </div>
                                            @endif
                                        @endif

                                        @if (filled($message->body) && $message->type !== 'audio')
                                            <p class="whitespace-pre-wrap break-words text-sm leading-relaxed">{{ $message->body }}</p>
                                        @elseif (! in_array($message->type, ['image', 'audio'], true))
                                            <p class="text-sm leading-relaxed">Mensaje sin contenido</p>
                                        @endif

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

                                    <div class="mt-1 flex flex-wrap items-center gap-1 {{ $isOutbound ? 'justify-end' : 'justify-start' }}">
                                        @foreach ($message->reactions as $reaction)
                                            <span
                                                class="inline-flex min-h-7 items-center rounded-full border border-zinc-200 bg-white px-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-800"
                                                title="{{ $reaction->direction === \App\Enums\WhatsAppMessageDirection::Outbound ? 'Reacción enviada' : 'Reacción recibida' }}"
                                            >
                                                {{ $reaction->emoji }}
                                            </span>
                                        @endforeach

                                        @if ($message->canReceiveReaction())
                                            <flux:dropdown position="top" :align="$isOutbound ? 'end' : 'start'">
                                                <flux:button
                                                    type="button"
                                                    variant="ghost"
                                                    size="xs"
                                                    icon="face-smile"
                                                    aria-label="Reaccionar al mensaje"
                                                    class="opacity-60 transition group-hover:opacity-100 focus:opacity-100"
                                                />

                                                <flux:menu>
                                                    @foreach (['👍' => 'Me gusta', '❤️' => 'Me encanta', '😂' => 'Me divierte', '😮' => 'Me sorprende', '😢' => 'Me entristece', '🙏' => 'Gracias'] as $emoji => $label)
                                                        <flux:menu.item wire:click="react({{ $message->id }}, '{{ $emoji }}')">
                                                            <span class="me-2 text-lg">{{ $emoji }}</span> {{ $label }}
                                                        </flux:menu.item>
                                                    @endforeach
                                                </flux:menu>
                                            </flux:dropdown>
                                        @endif
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

                    @if ($selectedConversation->isBotPaused())
                        <flux:callout icon="pause-circle" color="sky" class="mb-3">
                            El bot está pausado en este chat. Puedes seguir respondiendo manualmente.
                        </flux:callout>
                    @endif

                    <form
                        wire:submit="send"
                        x-data="whatsappAudioRecorder($wire)"
                        x-on:livewire:navigating.window="dispose"
                    >
                        @if ($photo)
                            <div class="mb-2 flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-700 dark:bg-zinc-800">
                                <img src="{{ $photo->temporaryUrl() }}" alt="Foto seleccionada" class="size-16 rounded-lg object-cover" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $photo->getClientOriginalName() }}</p>
                                    <p class="text-xs text-zinc-500">Lista para enviar</p>
                                </div>
                                <flux:button type="button" wire:click="removePhoto" variant="ghost" size="sm" icon="x-mark" aria-label="Quitar foto" />
                            </div>
                        @endif

                        @if ($audio)
                            <div class="mb-2 flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-700 dark:bg-zinc-800">
                                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                    <flux:icon.speaker-wave class="size-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $audio->getClientOriginalName() }}</p>
                                    <p class="text-xs text-zinc-500">Audio listo para enviar</p>
                                </div>
                                <flux:button type="button" wire:click="removeAudio" variant="ghost" size="sm" icon="x-mark" aria-label="Quitar audio" />
                            </div>
                        @endif

                        <div
                            x-cloak
                            x-show="recording || uploading || error"
                            class="mb-2 flex flex-wrap items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                        >
                            <template x-if="recording">
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <span class="size-2.5 animate-pulse rounded-full bg-red-500"></span>
                                    <span class="font-medium text-zinc-800 dark:text-zinc-100">Grabando</span>
                                    <span class="tabular-nums text-zinc-500" x-text="formattedDuration"></span>
                                </div>
                            </template>

                            <template x-if="uploading">
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <flux:icon.arrow-path class="size-4 animate-spin text-emerald-600" />
                                    <span class="text-zinc-600 dark:text-zinc-300">Preparando audio...</span>
                                    <span class="tabular-nums text-zinc-500" x-text="`${progress}%`"></span>
                                </div>
                            </template>

                            <template x-if="error">
                                <p class="min-w-0 flex-1 text-red-600 dark:text-red-400" x-text="error"></p>
                            </template>

                            <div x-show="recording" class="ms-auto flex items-center gap-1">
                                <flux:button type="button" x-on:click="cancel" variant="ghost" size="sm" icon="trash" aria-label="Cancelar grabación" />
                                <flux:button type="button" x-on:click="stop" variant="primary" size="sm" icon="stop" aria-label="Terminar grabación" />
                            </div>
                        </div>

                        <flux:composer
                            wire:model="reply"
                            name="reply"
                            submit="enter"
                            rows="1"
                            max-rows="5"
                            placeholder="Escribe un mensaje"
                            :disabled="! $selectedConversation->hasOpenCustomerServiceWindow()"
                        >
                            <x-slot name="actionsLeading">
                                <div class="flex items-center">
                                    <flux:file-upload wire:model="photo" accept="image/jpeg,image/png">
                                        <flux:button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            icon="photo"
                                            aria-label="Adjuntar foto"
                                            :disabled="! $selectedConversation->hasOpenCustomerServiceWindow()"
                                        />
                                    </flux:file-upload>

                                    <flux:file-upload wire:model="audio" accept=".aac,.amr,.mp3,.m4a,.mp4,.ogg,audio/aac,audio/amr,audio/mpeg,audio/mp4,audio/ogg">
                                        <flux:button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            icon="paper-clip"
                                            aria-label="Adjuntar audio"
                                            :disabled="! $selectedConversation->hasOpenCustomerServiceWindow()"
                                        />
                                    </flux:file-upload>

                                    <flux:button
                                        type="button"
                                        x-on:click="start"
                                        x-bind:disabled="recording || uploading"
                                        variant="ghost"
                                        size="sm"
                                        icon="microphone"
                                        aria-label="Grabar audio"
                                        :disabled="! $selectedConversation->hasOpenCustomerServiceWindow()"
                                    />
                                </div>
                            </x-slot>

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
                        <flux:error name="photo" />
                        <flux:error name="audio" />
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
