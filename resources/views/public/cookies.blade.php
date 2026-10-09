<x-layouts::public :title="__('public.cookies.title')">
    <section class="mx-auto max-w-3xl space-y-4 px-4 py-8">
        <h1 class="text-2xl text-ink">{{ __('public.cookies.title') }}</h1>
        <p class="rounded-lg bg-warn-bg p-3 text-sm font-semibold text-warn">{{ __('public.legal.draft_notice') }}</p>
        @foreach (__('public.cookies.sections') as $section)
            <h2 class="pt-2 text-lg text-ink">{{ $section['heading'] }}</h2>
            <p class="text-base text-ink">{{ $section['body'] }}</p>
        @endforeach
    </section>
</x-layouts::public>
