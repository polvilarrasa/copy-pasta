<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MilestoneMetric;
use App\Enums\NotificationType;
use App\Models\Copypasta;
use App\Models\User;
use App\Notifications\CopypastaMilestoneNotification;

class NotifyCopypastaMilestone
{
    /** Milestones of one copy-pasta reached within this long of the unread notification join it. */
    public const GROUPING_MINUTES = 60;

    /**
     * Notifies the author of a milestone. When the copy-pasta already has an unread milestone notification created less
     * than an hour ago, the milestone is added to it instead of creating another one.
     */
    public function handle(User $author, Copypasta $copypasta, MilestoneMetric $metric, int $threshold): void
    {
        if (! $author->wantsNotification(NotificationType::Milestone)) {
            return;
        }

        $existing = $author->unreadNotifications()
            ->where('type', NotificationType::Milestone->value)
            ->where('created_at', '>', now()->subMinutes(self::GROUPING_MINUTES))
            ->where('data->copypasta_id', $copypasta->getKey())
            ->latest()
            ->first();

        if ($existing === null) {
            $author->notify(new CopypastaMilestoneNotification($copypasta, $metric, $threshold));

            return;
        }

        /** @var array{copypasta_id: string, milestones: list<array{metric: string, threshold: int}>} $data */
        $data = $existing->data;
        $data['milestones'][] = ['metric' => $metric->value, 'threshold' => $threshold];

        $existing->forceFill(['data' => $data])->save();
    }
}
