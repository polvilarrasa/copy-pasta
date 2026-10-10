<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GrantVeteranAchievements as GrantVeteranAchievementsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('achievements:grant-veterans')]
#[Description('Evalúa Veterano para las cuentas con un año de antigüedad')]
class GrantVeteranAchievements extends Command
{
    public function handle(GrantVeteranAchievementsAction $grantVeterans): int
    {
        $this->info('Cuentas evaluadas: '.$grantVeterans->handle().'.');

        return self::SUCCESS;
    }
}
