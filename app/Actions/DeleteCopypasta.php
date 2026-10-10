<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteCopypasta
{
    public function __construct(private AdjustPublishedProgress $adjustPublishedProgress) {}

    /**
     * The author deletes their copy-pasta (soft delete). It stops counting towards their "published" progress, but
     * achievements already earned are kept.
     */
    public function handle(User $actor, Copypasta $copypasta): void
    {
        Gate::forUser($actor)->authorize('delete', $copypasta);

        DB::transaction(function () use ($copypasta): void {
            $countedBefore = AdjustPublishedProgress::counts($copypasta);

            $copypasta->delete();

            $this->adjustPublishedProgress->handle($copypasta, $countedBefore);
        });
    }
}
