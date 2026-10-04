<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use Illuminate\Support\Facades\Cache;

class RecordCopypastaCopy
{
    /**
     * Count a copy at most once per copy-pasta, visitor and hour. Returns whether the counter moved.
     */
    public function handle(Copypasta $copypasta, string $visitorKey): bool
    {
        $bucket = now()->format('YmdH');
        $cacheKey = sprintf('copypasta-copy:%s:%s:%s', $copypasta->getKey(), hash('sha256', $visitorKey), $bucket);

        if (! Cache::add($cacheKey, true, now()->addHour())) {
            return false;
        }

        Copypasta::query()->whereKey($copypasta->getKey())->increment('copies_count');

        return true;
    }
}
