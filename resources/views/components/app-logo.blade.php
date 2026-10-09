<a {{ $attributes->class(['flex items-center gap-2 text-md text-ink']) }}>
    <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-accent text-on-accent">
        <x-app-logo-icon class="size-5 fill-current" />
    </span>
    {{ config('app.name', 'Laravel') }}
</a>
