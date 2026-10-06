@props(['name', 'label', 'hint' => null, 'rows' => 4])

@php
    $id = $attributes->get('id', 'field-'.$name);
    $error = $errors->first($name);
    $describedBy = collect([$hint ? $id.'-hint' : null, $error ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<div class="grid gap-2">
    <label for="{{ $id }}" class="text-sm font-semibold text-ink">{{ $label }}</label>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->except(['id', 'rows'])->class([
            'w-full rounded-lg border border-border bg-surface px-3.5 py-3 text-md text-ink placeholder:text-muted focus-visible:outline-none focus-visible:shadow-focus',
        ]) }}
    >{{ $slot }}</textarea>
    @if ($hint)
        <p id="{{ $id }}-hint" class="text-sm text-muted">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="text-sm font-semibold text-bad">{{ $error }}</p>
    @endif
</div>
