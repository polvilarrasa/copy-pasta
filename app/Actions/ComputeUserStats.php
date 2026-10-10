<?php

declare(strict_types=1);

namespace App\Actions;

use App\Console\Commands\AggregateEventStats;
use App\Models\Copypasta;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComputeUserStats
{
    public const CACHE_SECONDS = 600;

    private const SPANISH_MONTHS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    /**
     * Everything the private stats page shows, cached per user for 10 minutes. Includes the user's own hidden
     * copy-pastas (the owner already sees those) and excludes deleted ones; never reads the raw `events` table,
     * only `copypasta_daily_stats` and the denormalized counters.
     *
     * @return array<string, mixed>
     */
    public function handle(User $user): array
    {
        return Cache::remember("stats:user:{$user->getKey()}", self::CACHE_SECONDS, fn (): array => $this->compute($user));
    }

    /**
     * @return array<string, mixed>
     */
    private function compute(User $user): array
    {
        $today = Carbon::today();
        $currentFrom = $today->copy()->subDays(29);
        $previousFrom = $today->copy()->subDays(59);
        $previousTo = $today->copy()->subDays(30);

        $daily = DB::table('copypasta_daily_stats')
            ->join('copypastas', 'copypastas.id', '=', 'copypasta_daily_stats.copypasta_id')
            ->where('copypastas.user_id', $user->getKey())
            ->whereNull('copypastas.deleted_at')
            ->whereBetween('copypasta_daily_stats.date', [$previousFrom->toDateString(), $today->toDateString()])
            ->selectRaw('copypasta_daily_stats.date, sum(copies) as copies, sum(upvotes) as upvotes, sum(downvotes) as downvotes, sum(favorites) as favorites, sum(shares) as shares, sum(views) as views')
            ->groupBy('copypasta_daily_stats.date')
            ->get()
            ->keyBy(fn (object $row): string => (string) $row->date);

        $topTags = DB::table('tags')
            ->join('copypasta_tag', 'copypasta_tag.tag_id', '=', 'tags.id')
            ->join('copypastas', 'copypastas.id', '=', 'copypasta_tag.copypasta_id')
            ->where('copypastas.user_id', $user->getKey())
            ->whereNull('copypastas.deleted_at')
            ->groupBy('tags.id', 'tags.name', 'tags.color')
            ->orderByDesc('copies')
            ->limit(3)
            ->selectRaw('tags.name, tags.color, sum(copypastas.copies_count) as copies')
            ->get();

        $topTable = Copypasta::query()
            ->where('user_id', $user->getKey())
            ->orderByDesc('score')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'copies_count', 'upvotes_count', 'downvotes_count', 'favorites_count']);

        $growing = DB::table('copypasta_daily_stats')
            ->join('copypastas', 'copypastas.id', '=', 'copypasta_daily_stats.copypasta_id')
            ->where('copypastas.user_id', $user->getKey())
            ->whereNull('copypastas.deleted_at')
            ->where('copypasta_daily_stats.date', '>=', $today->copy()->subDays(6)->toDateString())
            ->groupBy('copypastas.id', 'copypastas.title', 'copypastas.slug')
            ->orderByDesc('growth')
            ->limit(1)
            ->selectRaw('copypastas.id, copypastas.title, copypastas.slug, sum(copies + upvotes - downvotes) as growth')
            ->first();

        $reportCounts = DB::table('reports')
            ->where('reporter_id', $user->getKey())
            ->selectRaw("count(*) filter (where status = 'accepted') as accepted, count(*) filter (where status = 'rejected') as rejected")
            ->first();

        $lastAggregatedAt = Cache::get(AggregateEventStats::LAST_RUN_CACHE_KEY);

        return [
            // Pre-formatted into plain strings, never Carbon instances: a Livewire component holding this array as a
            // public property re-hydrates it from a wire snapshot on every interaction, and Carbon objects nested
            // inside a plain array do not survive that unserialize cycle (the class loads too late).
            'range' => [
                'shortFrom' => $currentFrom->translatedFormat('j M'),
                'shortTo' => $today->translatedFormat('j M'),
                'longFrom' => $currentFrom->translatedFormat('j \d\e F'),
                'longTo' => $today->translatedFormat('j \d\e F'),
            ],
            'kpis' => $this->kpis($daily, $currentFrom, $today, $previousFrom, $previousTo),
            'series' => $this->series($daily, $currentFrom, $today),
            'visits' => $this->sumColumn($daily, $currentFrom, $today, 'views'),
            'topTags' => $topTags->map(fn (object $tag): array => [
                'name' => $tag->name,
                'color' => $tag->color,
                'copies' => (int) $tag->copies,
            ])->all(),
            'topTable' => $topTable->map(fn (Copypasta $copypasta): array => [
                'title' => $copypasta->title,
                'url' => route('copypastas.show', [$copypasta, $copypasta->slug]),
                'copies' => $copypasta->copies_count,
                'votes' => $copypasta->upvotes_count - $copypasta->downvotes_count,
                'saves' => $copypasta->favorites_count,
            ])->all(),
            'fastestGrowing' => $growing !== null && $growing->growth > 0 ? [
                'title' => $growing->title,
                'url' => route('copypastas.show', [$growing->id, $growing->slug]),
                'growth' => (int) $growing->growth,
            ] : null,
            'reliability' => $this->reliability($user, (int) ($reportCounts->accepted ?? 0), (int) ($reportCounts->rejected ?? 0)),
            'lastAggregatedAt' => $lastAggregatedAt instanceof CarbonInterface ? $lastAggregatedAt->diffForHumans() : null,
        ];
    }

    /**
     * @param  Collection<string, \stdClass>  $daily
     * @return array<string, mixed>
     */
    private function kpis(Collection $daily, Carbon $currentFrom, Carbon $currentTo, Carbon $previousFrom, Carbon $previousTo): array
    {
        $metrics = [
            'copies' => ['label' => __('public.stats.kpi.copies'), 'column' => 'copies', 'unit' => 'copias'],
            'votes' => ['label' => __('public.stats.kpi.votes'), 'column' => 'votes', 'unit' => 'votos'],
            'saves' => ['label' => __('public.stats.kpi.saves'), 'column' => 'favorites', 'unit' => 'guardados'],
            'shares' => ['label' => __('public.stats.kpi.shares'), 'column' => 'shares', 'unit' => 'compartidos'],
        ];

        $kpis = [];

        foreach ($metrics as $key => $metric) {
            $current = $this->sumColumn($daily, $currentFrom, $currentTo, $metric['column']);
            $previous = $this->sumColumn($daily, $previousFrom, $previousTo, $metric['column']);

            $kpis[$key] = [
                'label' => $metric['label'],
                'unit' => $metric['unit'],
                'total' => $current,
                'delta' => $previous === 0 ? null : round((($current - $previous) / $previous) * 100),
            ];
        }

        return $kpis;
    }

    /**
     * @param  Collection<string, \stdClass>  $daily
     * @return array<string, array<int, array{date: string, label: string, value: int}>>
     */
    private function series(Collection $daily, Carbon $from, Carbon $to): array
    {
        $columns = ['copies' => 'copies', 'votes' => 'votes', 'saves' => 'favorites', 'shares' => 'shares'];

        $series = array_fill_keys(array_keys($columns), []);

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $row = $daily->get($date->toDateString());

            foreach ($columns as $metric => $column) {
                $series[$metric][] = [
                    'date' => $date->toDateString(),
                    'label' => $this->dayLabel($date),
                    'value' => $this->valueFor($row, $column),
                ];
            }
        }

        return $series;
    }

    /**
     * @param  Collection<string, \stdClass>  $daily
     */
    private function sumColumn(Collection $daily, Carbon $from, Carbon $to, string $column): int
    {
        $sum = 0;

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $sum += $this->valueFor($daily->get($date->toDateString()), $column);
        }

        return $sum;
    }

    private function valueFor(?object $row, string $column): int
    {
        if ($row === null) {
            return 0;
        }

        if ($column === 'votes') {
            return (int) $row->upvotes - (int) $row->downvotes;
        }

        return (int) $row->{$column};
    }

    private function dayLabel(Carbon $date): string
    {
        return $date->day.' '.self::SPANISH_MONTHS[$date->month - 1];
    }

    /**
     * @return array<string, mixed>
     */
    private function reliability(User $user, int $accepted, int $rejected): array
    {
        $resolved = $accepted + $rejected;
        $percentage = $resolved === 0 ? null : round(($accepted / $resolved) * 100);

        return [
            'isTrusted' => $user->isTrusted(),
            'resolved' => $resolved,
            'accepted' => $accepted,
            'percentage' => $percentage,
            'qualifies' => $resolved >= 10 && $percentage !== null && $percentage >= 80,
        ];
    }
}
