<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\PruneNotifications as PruneNotificationsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:prune')]
#[Description('Borra las notificaciones leídas de más de 90 días y las no leídas de más de 180')]
class PruneNotifications extends Command
{
    public function handle(PruneNotificationsAction $pruneNotifications): int
    {
        $this->info('Notificaciones borradas: '.$pruneNotifications->handle().'.');

        return self::SUCCESS;
    }
}
