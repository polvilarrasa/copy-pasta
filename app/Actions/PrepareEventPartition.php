<?php

declare(strict_types=1);

namespace App\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PrepareEventPartition
{
    /**
     * Creates the monthly partition of `events` that holds the given month. Does nothing when it already exists.
     */
    public function handle(CarbonInterface $month): void
    {
        $start = $month->copy()->startOfMonth();

        DB::statement(sprintf(
            "CREATE TABLE IF NOT EXISTS %s PARTITION OF events FOR VALUES FROM ('%s') TO ('%s')",
            self::nameFor($start),
            $start->format('Y-m-d'),
            $start->copy()->addMonth()->format('Y-m-d'),
        ));
    }

    public static function nameFor(CarbonInterface $month): string
    {
        return sprintf('events_y%sm%s', $month->format('Y'), $month->format('m'));
    }
}
