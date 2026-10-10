<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\SyncCopypastaOgImage;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Brings a copy-pasta's Open Graph image up to date. Always queued: drawing it takes a few hundred milliseconds and
 * never happens inside a request.
 */
class SyncCopypastaOgImageJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 2;

    public int $timeout = 90;

    public function __construct(public string $copypastaId, public bool $force = false)
    {
        $this->afterCommit();
    }

    public function handle(SyncCopypastaOgImage $sync): void
    {
        $sync->handle($this->copypastaId, $this->force);
    }
}
