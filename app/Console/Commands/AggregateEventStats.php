<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AggregateCopypastaDailyStats;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('events:aggregate {--from= : Primer día a recalcular (AAAA-MM-DD); por defecto, ayer} {--to= : Último día (AAAA-MM-DD); por defecto, hoy}')]
#[Description('Recalcula los agregados diarios por copy-pasta a partir de los eventos')]
class AggregateEventStats extends Command
{
    public function handle(AggregateCopypastaDailyStats $aggregate): int
    {
        $from = $this->option('from') !== null ? Carbon::parse((string) $this->option('from')) : now()->subDay();
        $to = $this->option('to') !== null ? Carbon::parse((string) $this->option('to')) : now();

        $aggregate->handle($from->startOfDay(), $to->startOfDay());

        $this->info(sprintf('Agregados recalculados de %s a %s.', $from->toDateString(), $to->toDateString()));

        return self::SUCCESS;
    }
}
