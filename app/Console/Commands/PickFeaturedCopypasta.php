<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\PickFeaturedCopypasta as PickFeaturedCopypastaAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('featured:pick')]
#[Description('Elige el copy-pasta del día')]
class PickFeaturedCopypasta extends Command
{
    public function handle(PickFeaturedCopypastaAction $pick): int
    {
        $featured = $pick->handle();

        $this->info($featured === null ? 'Hoy no hay copy-pasta del día.' : 'Copy-pasta del día: '.$featured->copypasta_id.'.');

        return self::SUCCESS;
    }
}
