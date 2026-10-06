@props(['title'])

<div {{ $attributes->class(['flex flex-col items-center gap-3 rounded-3xl border border-dashed border-border bg-surface p-8 text-center']) }}>
    @isset($icon)
        <span class="text-muted" aria-hidden="true">{{ $icon }}</span>
    @endisset
    <h3 class="text-lg font-bold text-ink">{{ $title }}</h3>
    <p class="text-base text-muted">{{ $slot }}</p>
</div>
