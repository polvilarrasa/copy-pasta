<x-layouts::public :title="__('public.variants.title_create')">
    @if (auth()->user()->hasVerifiedEmail())
        <livewire:copypasta-form :parent="$copypasta" />
    @else
        @include('partials.verify-email-notice', ['title' => __('public.variants.unverified_title')])
    @endif
</x-layouts::public>
