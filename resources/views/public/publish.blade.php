<x-layouts::public :title="__('public.publish.title_create')">
    @if (auth()->user()->hasVerifiedEmail())
        <livewire:copypasta-form :prefill-title="$prefillTitle" :prefill-body="$prefillBody" />
    @else
        @include('partials.verify-email-notice', ['title' => __('public.publish.unverified_title')])
    @endif
</x-layouts::public>
