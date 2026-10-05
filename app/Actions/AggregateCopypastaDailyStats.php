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
                count(*) FILTER (WHERE type = 'vote_up'),
                count(*) FILTER (WHERE type = 'vote_down'),
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
