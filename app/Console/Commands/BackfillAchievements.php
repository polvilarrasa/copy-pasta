<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\BackfillAchievements as BackfillAchievementsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:backfill-achievements')]
#[Description('Calcula el progreso y los logros con los datos existentes, sin enviar notificaciones')]
class BackfillAchievements extends Command
{
    public function handle(BackfillAchievementsAction $backfill): int
    {
        $granted = $backfill->handle();

        $this->info('Logros concedidos: '.$granted.'. Sin notificaciones.');

        return self::SUCCESS;
    }
}
