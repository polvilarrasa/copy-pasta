<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\PrepareEventPartition;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('events:partitions')]
#[Description('Crea las particiones mensuales de eventos del mes en curso y del siguiente')]
class PrepareEventPartitions extends Command
{
    public function handle(PrepareEventPartition $prepare): int
    {
        $currentMonth = Carbon::now()->startOfMonth();

        foreach ([$currentMonth, $currentMonth->copy()->addMonth()] as $month) {
            $prepare->handle($month);
        }

        return self::SUCCESS;
    }
}
