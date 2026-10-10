<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MilestoneMetric;
use App\Enums\NotificationType;
use App\Models\Copypasta;
use Illuminate\Support\Facades\DB;

class DetectCopypastaMilestones
{
    public function __construct(private NotifyCopypastaMilestone $notifyMilestone) {}

    /**
     * Registers every threshold the counter has reached and notifies the author of the highest new one. Call it once
     * the transaction that moved the counter has committed. A milestone is registered once in the life of the
     * copy-pasta, so a counter that drops and climbs again never notifies twice.
     *
     * A hidden, unpublished or deleted copy-pasta, or an author who cannot be notified, registers nothing: the
     * milestone is detected later, if the copy-pasta is visible again. An author who switched the type off still gets
     * the milestone registered, so reactivating the type does not bring old news.
     */
    public function handle(Copypasta $copypasta, MilestoneMetric $metric): void
    {
        $fresh = Copypasta::query()->with('user')->find($copypasta->getKey());

        if ($fresh === null || $fresh->published_at === null || $fresh->isHidden() || ! $fresh->user->canBeNotified()) {
            return;
        }

        $count = $metric->count($fresh);

        $reached = array_values(array_filter(MilestoneMetric::THRESHOLDS, fn (int $threshold): bool => $threshold <= $count));

        $new = array_values(array_filter($reached, fn (int $threshold): bool => $this->register($fresh, $metric, $threshold)));

        if ($new === [] || ! $fresh->user->wantsNotification(NotificationType::Milestone)) {
            return;
        }

        $this->notifyMilestone->handle($fresh->user, $fresh, $metric, max($new));
    }

    private function register(Copypasta $copypasta, MilestoneMetric $metric, int $threshold): bool
    {
        return DB::table('copypasta_milestones')->insertOrIgnore([
            'copypasta_id' => $copypasta->getKey(),
            'metric' => $metric->value,
            'threshold' => $threshold,
            'created_at' => now(),
        ]) === 1;
    }
}
