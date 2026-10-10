<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\DB;

class PruneAttributedVisits
{
    public const RETENTION_DAYS = 2;

    /**
     * Deletes the dedup rows of past days. A row only blocks a second visit on its own day, so keeping two days is
     * enough to cover the time zone edge. Returns how many were deleted.
     */
    public function handle(): int
    {
        return DB::table('attributed_visits')
            ->where('visited_on', '<', now()->subDays(self::RETENTION_DAYS)->toDateString())
            ->delete();
    }
}
