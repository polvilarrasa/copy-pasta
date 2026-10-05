<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecordFeedImpressions
{
    /**
     * Adds one impression for each copy-pasta shown in a feed page, for today. One statement for the whole page, and no
     * user or event is written.
     *
     * @param  Collection<int, string>  $copypastaIds
     */
    public function handle(Collection $copypastaIds): void
    {
        if ($copypastaIds->isEmpty()) {
            return;
        }

        DB::statement(<<<'SQL'
            INSERT INTO copypasta_daily_stats (copypasta_id, date, impressions)
            SELECT shown.copypasta_id, ?::date, 1
            FROM unnest(?::char(26)[]) AS shown(copypasta_id)
            ON CONFLICT (copypasta_id, date) DO UPDATE SET impressions = copypasta_daily_stats.impressions + 1
            SQL, [now()->toDateString(), '{'.$copypastaIds->implode(',').'}']);
    }
}
