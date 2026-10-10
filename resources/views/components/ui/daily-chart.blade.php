@props(['title', 'description', 'series', 'unit'])

@php
    $chartId = 'daily-chart-'.Illuminate\Support\Str::random(8);
    $values = array_column($series, 'value');
    $max = max(1, max($values));
    $step = $max > 200 ? 100 : ($max > 60 ? 50 : ($max > 20 ? 10 : 5));
    $top = max((int) (ceil($max / $step) * $step), 1);

    $plotWidth = 600;
    $plotHeight = 170;
    $baseline = 190;
    $barCount = max(count($series), 1);
    $slot = $plotWidth / $barCount;
    $barWidth = $slot * 0.7;

    $bars = array_values(array_map(function (array $point, int $index) use ($slot, $barWidth, $plotHeight, $baseline, $top): array {
        $height = $point['value'] > 0 ? max(2, (int) round($point['value'] / $top * $plotHeight)) : 0;

        return [
            'x' => round($index * $slot + ($slot - $barWidth) / 2, 1),
            'y' => $baseline - $height,
            'height' => $height,
        ];
    }, $series, array_keys($series)));

    $gridLines = array_map(fn (int $level): array => [
        'y' => $baseline - ($level / 3 * $plotHeight),
        'value' => (int) round($top * $level / 3),
    ], [0, 1, 2, 3]);

    $total = array_sum($values);
    $totalReadout = __('public.stats.readout_total', ['value' => number_format($total, 0, ',', '.'), 'unit' => $unit]);
    $dayReadouts = array_map(
        fn (array $point): string => __('public.stats.readout_day', ['day' => $point['label'], 'value' => number_format($point['value'], 0, ',', '.'), 'unit' => $unit]),
        $series,
    );
@endphp

<div
    x-data="{
        hover: null,
        totalReadout: @js($totalReadout),
        dayReadouts: @js($dayReadouts),
        get readout() {
            return this.hover === null ? this.totalReadout : this.dayReadouts[this.hover];
        },
    }"
    {{ $attributes }}
>
    <p class="min-h-6 text-sm font-semibold text-muted tabular-nums" x-text="readout"></p>

    <svg viewBox="0 0 {{ $plotWidth }} {{ $baseline + 20 }}" role="img" aria-labelledby="{{ $chartId }}-title {{ $chartId }}-desc" class="mt-2 w-full">
        <title id="{{ $chartId }}-title">{{ $title }}</title>
        <desc id="{{ $chartId }}-desc">{{ $description }}</desc>

        @foreach ($gridLines as $line)
            <line x1="0" y1="{{ $line['y'] }}" x2="{{ $plotWidth }}" y2="{{ $line['y'] }}" class="stroke-border" stroke-width="1" />
            <text x="0" y="{{ $line['y'] - 5 }}" class="fill-muted" font-size="11">{{ number_format($line['value'], 0, ',', '.') }}</text>
        @endforeach

        @foreach ($bars as $index => $bar)
            <rect
                x="{{ $bar['x'] }}"
                y="{{ $bar['y'] }}"
                width="{{ $barWidth }}"
                height="{{ $bar['height'] }}"
                rx="2"
                class="fill-vote"
                x-on:mouseenter="hover = {{ $index }}"
                x-on:mouseleave="hover = null"
                :opacity="hover === null || hover === {{ $index }} ? 1 : 0.55"
            ><title>{{ $dayReadouts[$index] }}</title></rect>
        @endforeach
    </svg>

    <table class="sr-only">
        <caption>{{ $title }}</caption>
        <thead>
            <tr>
                <th scope="col">{{ __('public.stats.chart_day') }}</th>
                <th scope="col">{{ $title }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($series as $point)
                <tr>
                    <th scope="row">{{ $point['label'] }}</th>
                    <td>{{ $point['value'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
