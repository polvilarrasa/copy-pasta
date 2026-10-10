<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Support\UnreadNotificationCount;
use Illuminate\Notifications\Events\NotificationSent;

class ForgetUnreadNotificationCount
{
    public function __construct(private UnreadNotificationCount $unreadCount) {}

    public function handle(NotificationSent $event): void
    {
        if ($event->channel === 'database' && $event->notifiable instanceof User) {
            $this->unreadCount->forget($event->notifiable);
        }
    }
}
