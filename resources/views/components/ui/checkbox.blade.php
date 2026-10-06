@props(['name', 'label'])

<label class="flex min-h-11 cursor-pointer items-center gap-3 text-md text-ink">
    <input
        type="checkbox"
        name="{{ $name }}"
        {{ $attributes->class(['size-5 shrink-0 accent-vote focus-visible:outline-none focus-visible:shadow-focus']) }}
    >
    <span>{{ $label }}</span>
</label>
