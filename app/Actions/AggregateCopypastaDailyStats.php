<?php

declare(strict_types=1);

namespace App\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AggregateCopypastaDailyStats
{
    /**
     * Rebuilds the event columns of `copypasta_daily_stats` for the days from $from to $to, both included. Re-running
     * over the same days gives the same totals. Impressions are left untouched: they are not events.
     *
     * Upvotes and downvotes are net (decision 8 of Fase V2): a vote_up/vote_down/vote_removed event carries the
     * voter's previous and next value in its context, and each day sums the deltas between them, so a vote cast one
     * day and changed or retracted another day is only ever counted on the day it changed.
     */
    public function handle(CarbonInterface $from, CarbonInterface $to): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO copypasta_daily_stats
                (copypasta_id, date, views, copies, shares, upvotes, downvotes, favorites, impressions)
            SELECT
                copypasta_id,
                created_at::date,
                count(*) FILTER (WHERE type = 'detail_view'),
                count(*) FILTER (WHERE type = 'copy'),
                count(*) FILTER (WHERE type = 'share'),
                sum(CASE WHEN type IN ('vote_up', 'vote_down', 'vote_removed')
                    THEN (coalesce(context->>'next', '') = '1')::int - (coalesce(context->>'previous', '') = '1')::int
                    ELSE 0 END),
                sum(CASE WHEN type IN ('vote_up', 'vote_down', 'vote_removed')
                    THEN (coalesce(context->>'next', '') = '-1')::int - (coalesce(context->>'previous', '') = '-1')::int
                    ELSE 0 END),
                count(*) FILTER (WHERE type = 'favorite_add') - count(*) FILTER (WHERE type = 'favorite_remove'),
                0
            FROM events
            WHERE copypasta_id IS NOT NULL
                AND created_at >= ?
                AND created_at < ?
            GROUP BY copypasta_id, created_at::date
            ON CONFLICT (copypasta_id, date) DO UPDATE SET
                views = EXCLUDED.views,
                copies = EXCLUDED.copies,
                shares = EXCLUDED.shares,
                upvotes = EXCLUDED.upvotes,
                downvotes = EXCLUDED.downvotes,
                favorites = EXCLUDED.favorites
            SQL, [$from->copy()->startOfDay(), $to->copy()->addDay()->startOfDay()]);
    }
}
