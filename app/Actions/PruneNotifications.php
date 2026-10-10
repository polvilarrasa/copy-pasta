<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\UnreadNotificationCount;
use Illuminate\Notifications\DatabaseNotification;

class PruneNotifications
{
    public const READ_RETENTION_DAYS = 90;

    public const UNREAD_RETENTION_DAYS = 180;

    public function __construct(private UnreadNotificationCount $unreadCount) {}

    /**
     * Deletes read notifications older than 90 days and unread ones older than 180, by creation date. Returns how many
     * were deleted.
     */
    public function handle(): int
    {
        $read = DatabaseNotification::query()
            ->whereNotNull('read_at')
            ->where('created_at', '<', now()->subDays(self::READ_RETENTION_DAYS))
            ->delete();

        $unreadQuery = DatabaseNotification::query()
            ->whereNull('read_at')
            ->where('created_at', '<', now()->subDays(self::UNREAD_RETENTION_DAYS));

        $affectedUsers = (clone $unreadQuery)->distinct()->pluck('notifiable_id');
        $unread = $unreadQuery->delete();

        $affectedUsers->each(fn (mixed $id) => $this->unreadCount->forget((int) $id));

        return $read + $unread;
    }
}
