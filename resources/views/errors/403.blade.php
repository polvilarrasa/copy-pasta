<x-layouts::public :title="__('public.errors.403.title')">
    <section class="mx-auto max-w-xl px-4 py-16 text-center">
        <p class="text-sm font-bold text-muted">403</p>
        <h1 class="mt-2 text-2xl text-ink">{{ __('public.errors.403.title') }}</h1>
        <p class="mt-4 text-base text-muted">{{ __('public.errors.403.body') }}</p>
        <a href="{{ route('home') }}" class="mt-8 inline-block text-ink underline">{{ __('public.errors.back_home') }}</a>
    </section>
</x-layouts::public>
