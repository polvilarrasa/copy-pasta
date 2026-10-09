<x-layouts::public :title="__('public.notice.title')">
    <section class="mx-auto max-w-xl space-y-6 px-4 py-8">
        <h1 class="text-2xl text-ink">{{ __('public.notice.title') }}</h1>
        <p class="text-base text-muted">{{ __('public.notice.description', ['title' => $copypasta->title]) }}</p>

        @if (session('status'))
            <p class="rounded-lg bg-ok-bg p-3 text-sm font-semibold text-ok">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('notice.store', $copypasta) }}" class="space-y-4">
            @csrf

            <x-ui.input name="email" type="email" :label="__('public.notice.email')" required maxlength="255" value="{{ old('email') }}" />

            <x-ui.select name="reason" :label="__('public.notice.reason')" required>
                @foreach (\App\Enums\ReportReason::cases() as $reason)
                    <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ __('moderation.reasons.'.$reason->value) }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.textarea name="details" :label="__('public.notice.details')" maxlength="500" rows="4">{{ old('details') }}</x-ui.textarea>

            <x-ui.button type="submit" variant="primary">{{ __('public.notice.submit') }}</x-ui.button>
        </form>
    </section>
</x-layouts::public>
