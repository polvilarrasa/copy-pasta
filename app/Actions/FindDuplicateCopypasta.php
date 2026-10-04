<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;

class FindDuplicateCopypasta
{
    /**
     * A visible copy-pasta with the same normalized body, ignoring the given one.
     */
    public function handle(string $body, ?Copypasta $except = null): ?Copypasta
    {
        return Copypasta::query()
            ->visible()
            ->where('body_hash', Copypasta::hashBody($body))
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->first();
    }
}
