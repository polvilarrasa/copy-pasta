<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\RebuildAffinities as RebuildAffinitiesAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:rebuild-affinities')]
#[Description('Recalcula las afinidades por etiqueta desde votos, favoritos, copias y descartes (úsalo al cambiar los pesos)')]
class RebuildAffinities extends Command
{
    public function handle(RebuildAffinitiesAction $rebuild): int
    {
        $this->info('Afinidades recalculadas: '.$rebuild->handle().'.');

        return self::SUCCESS;
    }
}
