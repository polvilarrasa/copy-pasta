<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\CopypastaRevision;

class SaveCopypastaRevision
{
    /**
     * Stores the copy-pasta's current title and body as a new version.
     */
    public function handle(Copypasta $copypasta): CopypastaRevision
    {
        return CopypastaRevision::query()->create([
            'copypasta_id' => $copypasta->getKey(),
            'title' => $copypasta->title,
            'body' => $copypasta->body,
        ]);
    }

    /**
     * The version a report should point at: the latest one, created if the copy-pasta somehow has none yet.
     */
    public function currentFor(Copypasta $copypasta): CopypastaRevision
    {
        return $copypasta->revisions()->latest('id')->first() ?? $this->handle($copypasta);
    }
}
