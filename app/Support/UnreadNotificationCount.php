<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * The bell's counter, cached per user so a page load does not query notifications. Whatever creates, reads or deletes
 * an unread notification calls forget(); the TTL is only a safety net.
 */
class UnreadNotificationCount
{
    private const TTL_MINUTES = 60;

    public function get(User $user): int
    {
        return (int) Cache::remember(
            $this->key($user),
            now()->addMinutes(self::TTL_MINUTES),
            fn (): int => $user->unreadNotifications()->count(),
        );
    }

    public function forget(User|int $user): void
    {
        Cache::forget($this->key($user));
    }

    private function key(User|int $user): string
    {
        return 'notifications:unread:'.($user instanceof User ? $user->getKey() : $user);
    }
}
