<?php

declare(strict_types=1);

use App\Actions\AggregateCopypastaDailyStats;
use App\Actions\CastVote;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\TrackedEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => prepareEventPartitions());

/**
 * The net upvote or downvote delta of a single vote_up/vote_down/vote_removed event, from the previous/next values
 * its context carries (decision 8 of Fase V2).
 */
function voteDelta(TrackedEvent $event, int $value): int
{
    return (($event->context['next'] ?? null) === $value ? 1 : 0) - (($event->context['previous'] ?? null) === $value ? 1 : 0);
}

/**
 * Counts the events per copy-pasta and day directly from the events table, without the aggregation's SQL. Votes are
 * net: each vote_up/vote_down/vote_removed event's context carries the previous and next value.
 *
 * @return array<string, array<string, int>>
 */
function directCounts(): array
{
    return TrackedEvent::query()
        ->whereNotNull('copypasta_id')
        ->get()
        ->groupBy(fn (TrackedEvent $event): string => $event->copypasta_id.'|'.$event->created_at->toDateString())
        ->map(fn ($events): array => [
            'views' => $events->where('type', EventType::DetailView)->count(),
            'copies' => $events->where('type', EventType::Copy)->count(),
            'shares' => $events->where('type', EventType::Share)->count(),
            'upvotes' => $events->whereIn('type', [EventType::VoteUp, EventType::VoteDown, EventType::VoteRemoved])
                ->sum(fn (TrackedEvent $event): int => voteDelta($event, 1)),
            'downvotes' => $events->whereIn('type', [EventType::VoteUp, EventType::VoteDown, EventType::VoteRemoved])
                ->sum(fn (TrackedEvent $event): int => voteDelta($event, -1)),
            'favorites' => $events->where('type', EventType::FavoriteAdd)->count()
                - $events->where('type', EventType::FavoriteRemove)->count(),
        ])
        ->all();
}

/**
 * The aggregated rows, keyed the same way as {@see directCounts()}.
 *
 * @return array<string, array<string, int>>
 */
function aggregatedCounts(): array
{
    return collect(DB::table('copypasta_daily_stats')->get())
        ->keyBy(fn (object $row): string => $row->copypasta_id.'|'.$row->date)
        ->map(fn (object $row): array => [
            'views' => (int) $row->views,
            'copies' => (int) $row->copies,
            'shares' => (int) $row->shares,
            'upvotes' => (int) $row->upvotes,
            'downvotes' => (int) $row->downvotes,
            'favorites' => (int) $row->favorites,
        ])
        ->all();
}

test('los agregados diarios coinciden con un recuento directo de los eventos', function (): void {
    $first = Copypasta::factory()->create();
    $second = Copypasta::factory()->create();

    TrackedEvent::factory()->forCopypasta($first)->ofType(EventType::DetailView)->count(3)->at(now()->subDay()->setTime(12, 0))->create();
    TrackedEvent::factory()->forCopypasta($first)->ofType(EventType::Copy)->at(now()->subDay()->setTime(13, 0))->create();
    TrackedEvent::factory()->forCopypasta($first)->ofType(EventType::VoteUp)->withContext(['previous' => null, 'next' => 1])->count(2)->at(now()->subHours(2))->create();
    TrackedEvent::factory()->forCopypasta($second)->ofType(EventType::VoteDown)->withContext(['previous' => null, 'next' => -1])->at(now()->subHours(2))->create();
    TrackedEvent::factory()->forCopypasta($second)->ofType(EventType::Share)->at(now()->subHours(3))->create();
    TrackedEvent::factory()->forCopypasta($second)->ofType(EventType::FavoriteAdd)->count(2)->at(now()->subHours(4))->create();
    TrackedEvent::factory()->forCopypasta($second)->ofType(EventType::FavoriteRemove)->at(now()->subHours(4))->create();

    app(AggregateCopypastaDailyStats::class)->handle(now()->subDays(2), now());

    expect(aggregatedCounts())->toEqualCanonicalizing(directCounts());
});

test('volver a calcular los mismos días da los mismos totales', function (): void {
    $copypasta = Copypasta::factory()->create();
    TrackedEvent::factory()->forCopypasta($copypasta)->ofType(EventType::Copy)->count(4)->at(now()->subHour())->create();

    $aggregate = app(AggregateCopypastaDailyStats::class);
    $aggregate->handle(now()->subDay(), now());
    $aggregate->handle(now()->subDay(), now());

    expect(DB::table('copypasta_daily_stats')->sole()->copies)->toBe(4);
});

test('votar arriba, cambiar a abajo y retirarlo deja los agregados del día en 0 y 0', function (): void {
    $voter = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $vote = app(CastVote::class);

    $vote->handle($voter, $copypasta, 1);
    $vote->handle($voter, $copypasta, -1);
    $vote->handle($voter, $copypasta, -1);

    app(AggregateCopypastaDailyStats::class)->handle(now()->subDay(), now());

    expect(DB::table('copypasta_daily_stats')->sole())
        ->upvotes->toBe(0)
        ->downvotes->toBe(0);
});

test('recalcular no toca las impresiones, que no salen de los eventos', function (): void {
    $copypasta = Copypasta::factory()->create();
    DB::table('copypasta_daily_stats')->insert([
        'copypasta_id' => $copypasta->getKey(),
        'date' => now()->toDateString(),
        'impressions' => 12,
    ]);
    // Today's own first second: an hour back would land on yesterday between 00:00 and 01:00.
    TrackedEvent::factory()->forCopypasta($copypasta)->ofType(EventType::DetailView)->at(now()->startOfDay()->addSecond())->create();

    app(AggregateCopypastaDailyStats::class)->handle(now()->subDay(), now());

    expect(DB::table('copypasta_daily_stats')->sole())
        ->views->toBe(1)
        ->impressions->toBe(12);
});
