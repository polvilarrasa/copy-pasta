<x-layouts::public :title="__('public.cookies.title')">
    <section class="mx-auto max-w-3xl space-y-4 px-4 py-8">
        <h1 class="text-2xl font-bold">{{ __('public.cookies.title') }}</h1>
        <p class="rounded-md bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">{{ __('public.legal.draft_notice') }}</p>
        @foreach (__('public.cookies.sections') as $section)
            <h2 class="pt-2 text-lg font-semibold">{{ $section['heading'] }}</h2>
            <p class="text-zinc-700">{{ $section['body'] }}</p>
        @endforeach
    </section>
</x-layouts::public>
