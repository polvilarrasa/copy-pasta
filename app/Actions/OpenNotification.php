<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Models\User;
use App\Support\NotificationPresenter;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;

class OpenNotification
{
    public function __construct(
        private MarkNotificationsRead $markRead,
        private RecordEvent $recordEvent,
        private NotificationPresenter $presenter,
    ) {}

    /**
     * Opens a notification: marks it as read, records the open and returns where it leads (null when its content is
     * gone). It is an action and not a link on purpose: a GET that changed state would be triggered by link prefetching.
     * During an impersonation nothing is marked and no event is recorded.
     */
    public function handle(User $user, string $notificationId): ?string
    {
        $notification = DatabaseNotification::query()->findOrFail($notificationId);

        Gate::forUser($user)->authorize('update', $notification);

        if (! is_impersonating()) {
            $this->recordEvent->handle(EventType::NotificationOpen, $user, null, ['type' => $notification->type]);
        }

        $this->markRead->one($user, $notification);

        return $this->presenter->present(collect([$notification]))->first()?->url;
    }
}
