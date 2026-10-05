<x-layouts::public :title="__('public.notice.title')">
    <section class="mx-auto max-w-xl space-y-6 px-4 py-8">
        <h1 class="text-2xl font-bold">{{ __('public.notice.title') }}</h1>
        <p class="text-sm text-zinc-600">{{ __('public.notice.description', ['title' => $copypasta->title]) }}</p>

        @if (session('status'))
            <p class="rounded-md bg-green-50 p-3 text-sm text-green-800 ring-1 ring-green-200">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('notice.store', $copypasta) }}" class="space-y-4">
            @csrf

            <label class="block text-sm">
                <span class="font-medium">{{ __('public.notice.email') }}</span>
                <input name="email" type="email" required maxlength="255" value="{{ old('email') }}" class="mt-1 w-full rounded-md border-zinc-300">
            </label>

            <label class="block text-sm">
                <span class="font-medium">{{ __('public.notice.reason') }}</span>
                <select name="reason" required class="mt-1 w-full rounded-md border-zinc-300">
                    @foreach (\App\Enums\ReportReason::cases() as $reason)
                        <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ __('moderation.reasons.'.$reason->value) }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block text-sm">
                <span class="font-medium">{{ __('public.notice.details') }}</span>
                <textarea name="details" maxlength="500" rows="4" class="mt-1 w-full rounded-md border-zinc-300">{{ old('details') }}</textarea>
            </label>

            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">{{ __('public.notice.submit') }}</button>
        </form>
    </section>
</x-layouts::public>
