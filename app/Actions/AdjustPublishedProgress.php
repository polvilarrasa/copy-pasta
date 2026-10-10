<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AchievementMetric;
use App\Models\Copypasta;

class AdjustPublishedProgress
{
    public function __construct(
        private AdjustAchievementProgress $adjustProgress,
        private QueueAchievementEvaluation $queueEvaluation,
    ) {}

    /**
     * Whether the copy-pasta counts towards its author's "published" metric: published, not hidden, not deleted.
     */
    public static function counts(Copypasta $copypasta): bool
    {
        return $copypasta->published_at !== null && $copypasta->hidden_at === null && $copypasta->deleted_at === null;
    }

    /**
     * Moves the author's "published" progress by the difference between how the copy-pasta counted before the change
     * and how it counts now. Call it inside the transaction that changed it, after saving.
     */
    public function handle(Copypasta $copypasta, bool $countedBefore): void
    {
        $delta = (self::counts($copypasta) ? 1 : 0) - ($countedBefore ? 1 : 0);

        $this->adjustProgress->add($copypasta->user_id, AchievementMetric::Published, $delta);

        if ($delta > 0) {
            $this->queueEvaluation->handle($copypasta->user_id, [AchievementMetric::Published]);
        }
    }
}
