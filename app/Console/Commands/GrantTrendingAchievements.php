<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GrantTrendingAchievements as GrantTrendingAchievementsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('achievements:grant-trending')]
#[Description('Concede En tendencia a los autores del top 10 semanal')]
class GrantTrendingAchievements extends Command
{
    public function handle(GrantTrendingAchievementsAction $grantTrending): int
    {
        $this->info('Autores en el top semanal: '.count($grantTrending->handle()).'.');

        return self::SUCCESS;
    }
}
