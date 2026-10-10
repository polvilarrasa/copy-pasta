<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * The cache entries of a member's "Para ti" feed: the list of candidates (regenerated every 30 minutes, on demand and
 * when the favorite tags change) and the copy-pastas already shown to them.
 */
class ForYouCache
{
    public function candidatesKey(User|int $user): string
    {
        return 'for-you:candidates:'.$this->id($user);
    }

    public function forgetCandidates(User|int $user): void
    {
        Cache::forget($this->candidatesKey($user));
    }

    /**
     * The ids shown most recently, oldest first.
     *
     * @return list<string>
     */
    public function seen(User|int $user): array
    {
        /** @var list<string> $seen */
        $seen = Cache::get($this->seenKey($user), []);

        return $seen;
    }

    /**
     * Remembers the ids as shown, keeping the latest 500, for seven days from the last time something was shown.
     *
     * @param  list<string>  $ids
     */
    public function markSeen(User|int $user, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $seen = array_values(array_unique([...array_diff($this->seen($user), $ids), ...$ids]));

        Cache::put(
            $this->seenKey($user),
            array_slice($seen, -((int) config('affinity.feed.seen_limit'))),
            now()->addDays((int) config('affinity.feed.seen_days')),
        );
    }

    private function seenKey(User|int $user): string
    {
        return 'for-you:seen:'.$this->id($user);
    }

    private function id(User|int $user): int
    {
        return $user instanceof User ? (int) $user->getKey() : $user;
    }
}
