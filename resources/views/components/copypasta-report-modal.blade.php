{{-- "Reportar" dialog. Opened by the "⋯" menu in ui.copypasta-card; one instance per card. --}}
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
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-scrim p-4" x-on:click.self="close()">
        <div role="dialog" aria-modal="true" aria-labelledby="copypasta-report-title-{{ $copypasta->getKey() }}" class="w-full max-w-sm rounded-3xl border border-border bg-surface p-5 text-ink shadow-pop">
            <h2 id="copypasta-report-title-{{ $copypasta->getKey() }}" class="text-xl font-extrabold">{{ __('public.report.title') }}</h2>

            <fieldset class="mt-4 space-y-2">
                <legend class="sr-only">{{ __('public.report.reason') }}</legend>
                @foreach ($reasons as $reason)
                    <label class="flex min-h-11 cursor-pointer items-center gap-3 text-md text-ink">
                        <input type="radio" name="reason-{{ $copypasta->getKey() }}" value="{{ $reason['value'] }}" x-model="reason" class="size-5 shrink-0 accent-vote focus-visible:outline-none focus-visible:shadow-focus">
                        <span>{{ $reason['label'] }}</span>
                    </label>
                @endforeach
            </fieldset>

            <div class="mt-4 grid gap-2">
                <label class="text-sm font-semibold text-ink" for="report-details-{{ $copypasta->getKey() }}">{{ __('public.report.details') }}</label>
                <textarea
                    id="report-details-{{ $copypasta->getKey() }}"
                    rows="3"
                    maxlength="500"
                    x-model="details"
                    class="w-full rounded-lg border border-border bg-surface px-3.5 py-3 text-md text-ink placeholder:text-muted focus-visible:outline-none focus-visible:shadow-focus"
                ></textarea>
                <p class="text-sm text-muted">{{ __('public.report.details_helper') }}</p>
            </div>

            <p x-show="error" x-text="error" role="alert" class="mt-3 text-sm font-semibold text-bad"></p>

            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button type="button" variant="ghost" x-on:click="close()">{{ __('public.report.cancel') }}</x-ui.button>
                <x-ui.button type="button" variant="primary" x-on:click="submit()" x-bind:disabled="sending">{{ __('public.report.submit') }}</x-ui.button>
            </div>
        </div>
    </div>
</div>
