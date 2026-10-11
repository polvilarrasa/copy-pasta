<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationType;
use App\Models\Copypasta;
use App\Notifications\CopypastaVariantNotification;

class NotifyCopypastaVariant
{
    /** Variants of one copy-pasta published within this long of its unread notification join it. */
    public const GROUPING_MINUTES = 60;

    /**
     * Tells the author of the original that a variant of it was published. Never the variant's own author, and never an
     * account that cannot be notified or switched the type off. When the original already has an unread notification
     * of this kind created less than an hour ago, the variant is counted in it instead of creating another one.
     */
    public function handle(Copypasta $variant): void
    {
        $parent = $variant->parent()->with('user')->first();

        if ($parent === null) {
            return;
        }

        $author = $parent->user;

        if ($author->getKey() === $variant->user_id || ! $author->wantsNotification(NotificationType::VariantPublished)) {
            return;
        }

        $existing = $author->unreadNotifications()
            ->where('type', NotificationType::VariantPublished->value)
            ->where('created_at', '>', now()->subMinutes(self::GROUPING_MINUTES))
            ->where('data->copypasta_id', $parent->getKey())
            ->latest()
            ->first();

        if ($existing === null) {
            $author->notify(new CopypastaVariantNotification($parent));

            return;
        }

        /** @var array{copypasta_id: string, count: int} $data */
        $data = $existing->data;
        $data['count']++;

        $existing->forceFill(['data' => $data])->save();
    }
}
