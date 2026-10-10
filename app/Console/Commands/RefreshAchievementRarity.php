<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\RefreshAchievementRarity as RefreshAchievementRarityAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('achievements:refresh-rarity')]
#[Description('Recalcula el porcentaje de cuentas activas que tiene cada logro')]
class RefreshAchievementRarity extends Command
{
    public function handle(RefreshAchievementRarityAction $refreshRarity): int
    {
        $this->info('Rareza recalculada para '.count($refreshRarity->handle()).' logros.');

        return self::SUCCESS;
    }
}
