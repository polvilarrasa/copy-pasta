@php
    $kpis = $stats['kpis'];
    $reliability = $stats['reliability'];
    $best = $stats['topTable'][0] ?? null;
    $growing = $stats['fastestGrowing'];
@endphp

<section class="mx-auto w-full max-w-3xl space-y-6 px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-extrabold tracking-tight text-ink">{{ __('public.stats.title') }}</h1>
            <span class="rounded-full bg-surface-2 px-2.5 py-1 text-xs font-bold text-muted">{{ __('public.stats.private_badge') }}</span>
        </div>

        <span class="text-sm font-semibold text-muted">
            {{ __('public.stats.range', ['from' => $stats['range']['shortFrom'], 'to' => $stats['range']['shortTo']]) }}
        </span>
    </div>

    @if ($stats['lastAggregatedAt'] !== null)
        <p class="text-sm text-muted">{{ __('public.stats.updated', ['time' => $stats['lastAggregatedAt']]) }}</p>
    @endif

    <div role="group" aria-label="{{ __('public.stats.title') }}" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($kpis as $key => $kpi)
            <button
                type="button"
                wire:click="selectMetric('{{ $key }}')"
                aria-pressed="{{ $metric === $key ? 'true' : 'false' }}"
                class="flex flex-col gap-1 rounded-2xl border-2 p-4 text-left focus-visible:outline-none focus-visible:shadow-focus {{ $metric === $key ? 'border-vote bg-surface' : 'border-border bg-surface' }}"
            >
                <span class="text-sm font-bold text-muted">{{ $kpi['label'] }}</span>
                <span class="text-3xl font-extrabold tracking-tight tabular-nums">{{ \App\Support\Numbers::abbreviate($kpi['total']) }}</span>
                <span class="flex items-center gap-1 text-sm font-bold {{ $kpi['delta'] === null ? 'text-muted' : ($kpi['delta'] >= 0 ? 'text-ok' : 'text-bad') }}">
                    {{ $kpi['delta'] === null ? __('public.stats.no_previous_data') : ($kpi['delta'] >= 0 ? '+' : '−').abs($kpi['delta']).'%' }}
                    <span class="font-medium text-muted">{{ __('public.stats.vs_previous') }}</span>
                </span>
            </button>
        @endforeach
    </div>

    <section class="flex flex-col gap-4 rounded-3xl border border-border bg-surface p-6">
        {{-- wire:key changes with the metric, so Livewire replaces this element instead of morphing it: the chart's
             Alpine state (the hover readout) is built once from the server data when x-data initializes, and a morph
             would leave that initial data stale after switching metrics. --}}
        <x-ui.daily-chart
            wire:key="daily-chart-{{ $metric }}"
            :series="$series"
            :unit="$unit"
            :title="__('public.stats.chart_title', ['metric' => $metricLabel])"
            :description="__('public.stats.chart_description', ['metric' => $metricLabel, 'from' => $stats['range']['longFrom'], 'to' => $stats['range']['longTo']])"
        />
    </section>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-border bg-surface p-4">
            <span class="text-sm font-bold text-muted">{{ __('public.stats.visits') }}</span>
            <p class="text-2xl font-extrabold tabular-nums">{{ \App\Support\Numbers::abbreviate($stats['visits']) }}</p>
        </div>

        <div class="rounded-2xl border border-border bg-surface p-4">
            <span class="text-sm font-bold text-muted">{{ __('public.stats.top_tags_title') }}</span>
            @if ($stats['topTags'] === [])
                <p class="mt-1 text-sm text-muted">{{ __('public.stats.top_tags_empty') }}</p>
            @else
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($stats['topTags'] as $tag)
                        <x-ui.tag-chip :name="$tag['name']" :color="$tag['color']" />
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-border bg-surface p-4">
            <span class="text-sm font-bold text-muted">{{ __('public.stats.best_title') }}</span>
            @if ($best)
                <a href="{{ $best['url'] }}" class="mt-1 block truncate font-bold text-ink hover:underline">{{ $best['title'] }}</a>
            @else
                <p class="mt-1 text-sm text-muted">{{ __('public.stats.no_data') }}</p>
            @endif
        </div>

        <div class="rounded-2xl border border-border bg-surface p-4">
            <span class="text-sm font-bold text-muted">{{ __('public.stats.growing_title') }}</span>
            @if ($growing)
                <a href="{{ $growing['url'] }}" class="mt-1 block truncate font-bold text-ink hover:underline">{{ $growing['title'] }}</a>
            @else
                <p class="mt-1 text-sm text-muted">{{ __('public.stats.no_data') }}</p>
            @endif
        </div>
    </div>

    <section class="rounded-2xl border border-border bg-surface p-4">
        <h2 class="text-lg font-extrabold">{{ __('public.stats.reliability_title') }}</h2>

        @if ($reliability['isTrusted'])
            <p class="mt-2 text-sm font-semibold text-ok">{{ __('public.stats.reliability_trusted') }}</p>
        @elseif ($reliability['resolved'] === 0)
            <p class="mt-2 text-sm text-muted">{{ __('public.stats.reliability_none') }}</p>
        @else
            <p class="mt-2 text-sm font-semibold text-ink">
                {{ __('public.stats.reliability_summary', ['accepted' => $reliability['accepted'], 'resolved' => $reliability['resolved'], 'percentage' => $reliability['percentage']]) }}
            </p>
            <div class="mt-2 flex items-center gap-2">
                <div role="progressbar" aria-valuemin="0" aria-valuemax="10" aria-valuenow="{{ min($reliability['resolved'], 10) }}" class="h-2 flex-1 overflow-hidden rounded-full bg-surface-2">
                    <div class="h-full rounded-full bg-vote" style="width: {{ min(100, round($reliability['resolved'] / 10 * 100)) }}%"></div>
                </div>
                <span class="text-xs font-bold text-muted">{{ __('public.stats.reliability_resolved_progress', ['resolved' => min($reliability['resolved'], 10)]) }}</span>
            </div>
            <p class="mt-2 text-sm text-muted">{{ __('public.stats.reliability_progress') }}</p>
        @endif
    </section>

    <section class="flex flex-col gap-3">
        <h2 class="text-xl font-extrabold tracking-tight">{{ __('public.stats.table_title') }}</h2>

        @if ($stats['topTable'] === [])
            <x-ui.empty-state :title="__('public.stats.table_empty')" />
        @else
            <div class="overflow-x-auto rounded-2xl border border-border bg-surface">
                <table class="w-full min-w-lg text-sm tabular-nums">
                    <thead>
                        <tr class="text-left text-xs text-muted">
                            <th scope="col" class="px-4 py-3 font-bold">{{ __('public.stats.table_title_column') }}</th>
                            <th scope="col" class="px-3 py-3 text-right font-bold">{{ __('public.stats.table_copies_column') }}</th>
                            <th scope="col" class="px-3 py-3 text-right font-bold">{{ __('public.stats.table_votes_column') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-bold">{{ __('public.stats.table_saves_column') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['topTable'] as $row)
                            <tr class="border-t border-border">
                                <th scope="row" class="px-4 py-3 text-left font-semibold">
                                    <a href="{{ $row['url'] }}" class="hover:underline">{{ $row['title'] }}</a>
                                </th>
                                <td class="px-3 py-3 text-right">{{ \App\Support\Numbers::abbreviate($row['copies']) }}</td>
                                <td class="px-3 py-3 text-right">{{ \App\Support\Numbers::abbreviate($row['votes']) }}</td>
                                <td class="px-4 py-3 text-right">{{ \App\Support\Numbers::abbreviate($row['saves']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</section>
