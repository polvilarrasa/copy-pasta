@props(['name', 'label'])

<label class="flex min-h-11 cursor-pointer items-center justify-between gap-3 text-md text-ink">
    <span>{{ $label }}</span>
    <span class="relative inline-flex shrink-0">
        <input
            type="checkbox"
            role="switch"
            name="{{ $name }}"
            {{ $attributes->class(['peer sr-only']) }}
        >
        <span class="block h-6 w-11 rounded-full bg-surface-2 transition-colors peer-checked:bg-vote peer-focus-visible:shadow-focus"></span>
        <span class="pointer-events-none absolute start-0.5 top-0.5 size-5 rounded-full bg-surface transition-transform peer-checked:translate-x-5"></span>
    </span>
</label>
