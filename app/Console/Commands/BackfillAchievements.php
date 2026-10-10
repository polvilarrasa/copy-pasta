<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\BackfillAchievements as BackfillAchievementsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:backfill-achievements {--force : Ejecutar aunque la app no esté en modo mantenimiento}')]
#[Description('Calcula el progreso y los logros con los datos existentes, sin enviar notificaciones')]
class BackfillAchievements extends Command
{
    public function handle(BackfillAchievementsAction $backfill): int
    {
        if (! $this->laravel->isDownForMaintenance() && ! $this->option('force')) {
            $this->error('Este comando sustituye los contadores de progreso y solo corre con la app en modo mantenimiento (php artisan down). Usa --force para saltarte la comprobación.');

            return self::FAILURE;
        }

        $granted = $backfill->handle();

        $this->info('Logros concedidos: '.$granted.'. Sin notificaciones.');

        return self::SUCCESS;
    }
}
