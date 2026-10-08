@props(['name', 'label', 'hint' => null])

@php
    $id = $attributes->get('id', 'field-'.$name);
    $error = $errors->first($name);
    $describedBy = collect([$hint ? $id.'-hint' : null, $error ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<div class="grid gap-2" x-data="{ shown: false }">
    <label for="{{ $id }}" class="text-sm font-semibold text-ink">{{ $label }}</label>
    <div class="relative">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="password"
            x-bind:type="shown ? 'text' : 'password'"
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            @if ($error) aria-invalid="true" @endif
            {{ $attributes->except('id')->class([
                'h-12 w-full rounded-lg border border-border bg-surface ps-3.5 pe-11 text-md text-ink placeholder:text-muted focus-visible:outline-none focus-visible:shadow-focus',
            ]) }}
        >
        <button
            type="button"
            x-on:click="shown = ! shown"
            :aria-label="shown ? @js(__('ui.password.hide')) : @js(__('ui.password.show'))"
            class="absolute inset-y-0 end-0 flex w-11 items-center justify-center text-muted focus-visible:outline-none focus-visible:shadow-focus"
        >
            <x-lucide-eye x-show="!shown" class="size-5" aria-hidden="true" />
            <x-lucide-eye-off x-show="shown" x-cloak class="size-5" aria-hidden="true" />
        </button>
    </div>
    @if ($hint)
        <p id="{{ $id }}-hint" class="text-sm text-muted">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="text-sm font-semibold text-bad">{{ $error }}</p>
    @endif
</div>
