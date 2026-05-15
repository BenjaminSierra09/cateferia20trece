@props([
    'label',
    'name',
    'wireModel' => null,
    'value' => '',
    'placeholder' => '',
    'country' => 'mx',
])

@php
    $inputId = $attributes->get('id') ?: $name;
    $hiddenId = $inputId.'_hidden';
    $errorKey = $wireModel !== null
        ? str_replace(['[', ']'], ['.', ''], $wireModel)
        : str_replace(['[', ']'], ['.', ''], $name);
@endphp

<div class="grid gap-2">
    <label for="{{ $inputId }}" data-flux-label>{{ $label }}</label>

    <input
        id="{{ $hiddenId }}"
        type="hidden"
        name="{{ $name }}"
        value="{{ $value }}"
        @if ($wireModel !== null)
            wire:model="{{ $wireModel }}"
        @endif
    />

    <div
        x-data="phoneInput({ hiddenInputId: @js($hiddenId), initialCountry: @js($country) })"
        x-init="init()"
        class="space-y-2"
    >
        <div wire:ignore>
            <input
                id="{{ $inputId }}"
                x-ref="input"
                type="tel"
                value="{{ $value }}"
                placeholder="{{ $placeholder }}"
                autocomplete="tel"
                inputmode="tel"
                @input.debounce.200ms="syncHiddenValue()"
                @blur="syncHiddenValue()"
                @countrychange="syncHiddenValue()"
                data-flux-control
                {{ $attributes->class('w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-900 shadow-xs transition placeholder:text-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white') }}
            />
        </div>
    </div>

    @error($errorKey)
        <div data-flux-error class="text-sm text-rose-600 dark:text-rose-400">{{ $message }}</div>
    @enderror
</div>
