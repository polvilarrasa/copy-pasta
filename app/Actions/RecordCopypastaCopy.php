<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class RecordCopypastaCopy
{
    public function __construct(private RecordEvent $recordEvent) {}

    /**
     * Count a copy at most once per copy-pasta, visitor and hour. Returns whether the counter moved; only a counted
     * copy is recorded as an event, so the events match the counter.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(Copypasta $copypasta, string $visitorKey, ?User $user = null, array $context = []): bool
    {
        $bucket = now()->format('YmdH');
        $cacheKey = sprintf('copypasta-copy:%s:%s:%s', $copypasta->getKey(), hash('sha256', $visitorKey), $bucket);

        if (! Cache::add($cacheKey, true, now()->addHour())) {
            return false;
        }

        Copypasta::query()->whereKey($copypasta->getKey())->increment('copies_count');

        $this->recordEvent->handle(EventType::Copy, $user, $copypasta, $context);

        return true;
    }
}
