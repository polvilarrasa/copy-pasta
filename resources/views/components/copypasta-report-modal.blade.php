{{-- "Reportar" dialog. Opened by the report button in copypasta-actions; one instance per card. --}}
@php
    $reasons = collect(\App\Enums\ReportReason::cases())->map(fn (\App\Enums\ReportReason $reason): array => [
        'value' => $reason->value,
        'label' => __('moderation.reasons.'.$reason->value),
    ])->values();
@endphp

<div
    x-data="copypastaReport({
        copypastaId: @js($copypasta->getKey()),
        url: @js(route('copypastas.report', $copypasta)),
        messages: {
            sent: @js(__('public.report.sent')),
            failed: @js(__('public.report.failed')),
            rateLimited: @js(__('public.report.rate_limited')),
        },
    })"
    x-on:report-open.window="$event.detail.copypasta === copypastaId && open($event.detail.context)"
    x-show="visible"
    x-cloak
    x-on:keydown.escape.window="close()"
>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/40 p-4" x-on:click.self="close()">
        <div role="dialog" aria-modal="true" aria-labelledby="copypasta-report-title-{{ $copypasta->getKey() }}" class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl">
            <h2 id="copypasta-report-title-{{ $copypasta->getKey() }}" class="text-base font-semibold text-zinc-900">{{ __('public.report.title') }}</h2>

            <fieldset class="mt-4 space-y-2">
                <legend class="sr-only">{{ __('public.report.reason') }}</legend>
                @foreach ($reasons as $reason)
                    <label class="flex items-center gap-2 text-sm text-zinc-800">
                        <input type="radio" name="reason-{{ $copypasta->getKey() }}" value="{{ $reason['value'] }}" x-model="reason" class="border-zinc-300">
                        <span>{{ $reason['label'] }}</span>
                    </label>
                @endforeach
            </fieldset>

            <div class="mt-4">
                <label class="block text-sm font-medium text-zinc-700" for="report-details-{{ $copypasta->getKey() }}">{{ __('public.report.details') }}</label>
                <textarea
                    id="report-details-{{ $copypasta->getKey() }}"
                    rows="3"
                    maxlength="500"
                    x-model="details"
                    class="mt-1 w-full rounded-md border-zinc-300 text-sm"
                ></textarea>
                <p class="mt-1 text-xs text-zinc-500">{{ __('public.report.details_helper') }}</p>
            </div>

            <p x-show="error" x-text="error" role="alert" class="mt-3 text-sm text-rose-700"></p>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" x-on:click="close()" class="rounded-md px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50">{{ __('public.report.cancel') }}</button>
                <button type="button" x-on:click="submit()" :disabled="sending" class="rounded-md bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-zinc-800 disabled:opacity-50">{{ __('public.report.submit') }}</button>
            </div>
        </div>
    </div>
</div>
