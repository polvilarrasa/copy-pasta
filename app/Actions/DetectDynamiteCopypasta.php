<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AchievementMetric;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\TrackedEvent;

class DetectDynamiteCopypasta
{
    /** Copies from others within the window that make a copy-pasta dynamite. */
    public const COPIES = 100;

    public const WINDOW_HOURS = 24;

    public function __construct(private AdjustAchievementProgress $adjustProgress) {}

    /**
     * Call it right after a copy has been recorded. Only a copy-pasta whose counter reached 100 is looked at, and
     * then the copies of the last 24 hours are counted with the (copypasta_id, created_at) index of events, leaving
     * out the author's own. Reaching the number raises the author's Dynamite flag. Returns whether it was reached.
     */
    public function handle(Copypasta $copypasta): bool
    {
        // The counter was incremented after this model was loaded.
        if ($copypasta->copies_count + 1 < self::COPIES) {
            return false;
        }

        $recent = TrackedEvent::query()
            ->where('copypasta_id', $copypasta->getKey())
            ->where('type', EventType::Copy)
            ->where('created_at', '>=', now()->subHours(self::WINDOW_HOURS))
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', '!=', $copypasta->user_id))
            ->count();

        if ($recent < self::COPIES) {
            return false;
        }

        $this->adjustProgress->raiseTo($copypasta->user_id, AchievementMetric::Dynamite, 1);

        return true;
    }
}
