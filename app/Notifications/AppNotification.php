<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Base of every in-app notification. It stores data (type and ids), never the finished text: the text is written from
 * lang/es when the notification is shown. A notification that its recipient cannot or does not want to receive is not
 * created at all, because via() comes back empty.
 */
abstract class AppNotification extends Notification
{
    abstract public function type(): NotificationType;

    /**
     * Ids and numbers that identify what happened, without the type.
     *
     * @return array<string, mixed>
     */
    abstract protected function payload(): array;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User && $notifiable->wantsNotification($this->type()) ? ['database'] : [];
    }

    /**
     * The `type` column holds the NotificationType value, so queries do not depend on class names.
     */
    public function databaseType(object $notifiable): string
    {
        return $this->type()->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['type' => $this->type()->value, ...$this->payload()];
    }
}
