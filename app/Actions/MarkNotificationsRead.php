<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Support\UnreadNotificationCount;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class MarkNotificationsRead
{
    public const MAX_PER_MINUTE = 120;

    public function __construct(private UnreadNotificationCount $unreadCount) {}

    /**
     * Marks one of the member's notifications as read. While staff impersonate the member nothing changes: a review by
     * the staff must not cost the member their unread state. Returns whether the notification was marked.
     */
    public function one(User $user, DatabaseNotification $notification): bool
    {
        Gate::forUser($user)->authorize('update', $notification);

        if (is_impersonating() || $notification->read_at !== null) {
            return false;
        }

        $this->throttle($user);

        $notification->markAsRead();
        $this->unreadCount->forget($user);

        return true;
    }

    /**
     * Marks every unread notification of the member as read. Returns how many were marked.
     */
    public function all(User $user): int
    {
        if (is_impersonating()) {
            return 0;
        }

        $this->throttle($user);

        $marked = $user->unreadNotifications()->update(['read_at' => now()]);
        $this->unreadCount->forget($user);

        return $marked;
    }

    private function throttle(User $user): void
    {
        throw_unless(
            RateLimiter::attempt('notifications-read:'.$user->getKey(), self::MAX_PER_MINUTE, fn (): bool => true, 60),
            new ThrottleRequestsException(__('notifications.errors.rate_limited')),
        );
    }
}
