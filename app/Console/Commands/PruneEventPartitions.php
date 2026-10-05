<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\DropExpiredEventPartitions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('events:prune')]
#[Description('Borra las particiones de eventos con más de 13 meses de antigüedad')]
class PruneEventPartitions extends Command
{
    public function handle(DropExpiredEventPartitions $drop): int
    {
        $dropped = $drop->handle(Carbon::now());

        $this->info(sprintf('Particiones borradas: %d.', $dropped));

        return self::SUCCESS;
    }
}
