<x-layouts::public :title="__('public.rules.title')">
    <section class="mx-auto max-w-3xl space-y-4 px-4 py-8">
        <h1 class="text-2xl text-ink">{{ __('public.rules.title') }}</h1>
        <p class="rounded-lg bg-warn-bg p-3 text-sm font-semibold text-warn">{{ __('public.legal.draft_notice') }}</p>
        <ul class="list-disc space-y-2 pl-5 text-base text-ink">
            @foreach (__('public.rules.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </section>
</x-layouts::public>
