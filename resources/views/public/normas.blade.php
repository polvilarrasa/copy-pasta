<x-layouts::public :title="__('public.rules.title')">
    <section class="mx-auto max-w-3xl space-y-4 px-4 py-8">
        <h1 class="text-2xl font-bold">{{ __('public.rules.title') }}</h1>
        <p class="rounded-md bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">{{ __('public.legal.draft_notice') }}</p>
        <ul class="list-disc space-y-2 pl-5 text-zinc-700">
            @foreach (__('public.rules.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </section>
</x-layouts::public>
