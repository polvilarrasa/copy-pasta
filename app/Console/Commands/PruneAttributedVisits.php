<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\PruneAttributedVisits as PruneAttributedVisitsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('visits:prune')]
#[Description('Borra las marcas de visitas atribuidas de días anteriores, que ya no pueden bloquear ninguna visita')]
class PruneAttributedVisits extends Command
{
    public function handle(PruneAttributedVisitsAction $pruneVisits): int
    {
        $this->info('Marcas borradas: '.$pruneVisits->handle().'.');

        return self::SUCCESS;
    }
}
