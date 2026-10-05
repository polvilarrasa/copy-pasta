<?php

declare(strict_types=1);

namespace App\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DropExpiredEventPartitions
{
    public const RETENTION_MONTHS = 13;

    /**
     * Drops the partitions of `events` whose month ended more than the retention window ago. Returns how many it dropped.
     */
    public function handle(CarbonInterface $now): int
    {
        $cutoff = $now->copy()->subMonths(self::RETENTION_MONTHS)->startOfMonth();

        $expired = collect(DB::select(<<<'SQL'
            SELECT child.relname AS name
            FROM pg_inherits
            JOIN pg_class child ON child.oid = pg_inherits.inhrelid
            JOIN pg_class parent ON parent.oid = pg_inherits.inhparent
            WHERE parent.relname = 'events'
            SQL))
            ->pluck('name')
            ->filter(fn (string $name): bool => $this->monthOf($name)?->lt($cutoff) === true);

        $expired->each(fn (string $name): mixed => DB::statement(sprintf('DROP TABLE %s', $name)));

        return $expired->count();
    }

    private function monthOf(string $partitionName): ?Carbon
    {
        if (preg_match('/^events_y(\d{4})m(\d{2})$/', $partitionName, $matches) !== 1) {
            return null;
        }

        return Carbon::createFromDate((int) $matches[1], (int) $matches[2], 1)->startOfMonth();
    }
}
