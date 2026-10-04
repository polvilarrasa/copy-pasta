<x-layouts::public :title="__('public.errors.403.title')">
    <section class="mx-auto max-w-xl px-4 py-16 text-center">
        <p class="text-sm font-semibold text-zinc-500">403</p>
        <h1 class="mt-2 text-2xl font-bold">{{ __('public.errors.403.title') }}</h1>
        <p class="mt-4 text-zinc-600">{{ __('public.errors.403.body') }}</p>
        <a href="{{ route('home') }}" class="mt-8 inline-block underline hover:text-zinc-900">{{ __('public.errors.back_home') }}</a>
    </section>
</x-layouts::public>
