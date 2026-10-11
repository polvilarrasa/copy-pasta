@props(['art', 'title', 'code' => null])

{{-- The "Vacios" look of the design: ASCII art on a soft panel, a title, a sentence and the way out. --}}
<section {{ $attributes->class(['mx-auto flex w-full max-w-md flex-col items-center gap-4 px-4 py-12 text-center']) }}>
    <pre aria-hidden="true" class="rounded-2xl bg-surface-2 px-6 py-4 text-left font-mono text-base leading-snug text-muted">{{ $art }}</pre>

    @if ($code)
        <p class="text-sm font-bold text-muted">{{ $code }}</p>
    @endif

    <h1 class="text-2xl text-ink">{{ $title }}</h1>
    <p class="text-base text-muted">{{ $slot }}</p>

    @isset($actions)
        <div class="mt-2 flex flex-wrap justify-center gap-2">{{ $actions }}</div>
    @endisset
</section>
