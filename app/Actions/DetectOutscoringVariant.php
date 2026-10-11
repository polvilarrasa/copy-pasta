<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AchievementMetric;
use App\Models\Copypasta;

class DetectOutscoringVariant
{
    /** Score a variant needs, besides being above its parent's. */
    public const MIN_SCORE = 10;

    public function __construct(
        private AdjustAchievementProgress $adjustProgress,
        private QueueAchievementEvaluation $queueEvaluation,
    ) {}

    /**
     * Call it after the votes of a copy-pasta changed. The copy-pasta counts as a variant (does its author now outscore
     * the parent?) and as a parent (does one of its visible variants now outscore it?). A visible variant with a score
     * of 10 or more and above its parent's raises its author's flag, which is never lowered. Returns the number of
     * authors whose flag was raised or already was.
     */
    public function handle(Copypasta $copypasta): int
    {
        $fresh = Copypasta::query()->find($copypasta->getKey());

        if ($fresh === null) {
            return 0;
        }

        $authorIds = [];

        if ($fresh->parent_id !== null && $fresh->isAvailable() && $fresh->score >= self::MIN_SCORE) {
            $parent = $fresh->parent;

            if ($parent !== null && $fresh->score > $parent->score) {
                $authorIds[] = $fresh->user_id;
            }
        }

        $outscoring = Copypasta::query()
            ->visible()
            ->where('parent_id', $fresh->getKey())
            ->where('score', '>=', self::MIN_SCORE)
            ->where('score', '>', $fresh->score)
            ->pluck('user_id')
            ->all();

        $authorIds = array_values(array_unique([...$authorIds, ...$outscoring]));

        foreach ($authorIds as $authorId) {
            $this->adjustProgress->raiseTo($authorId, AchievementMetric::OutscoringVariant, 1);
            $this->queueEvaluation->handle($authorId, [AchievementMetric::OutscoringVariant]);
        }

        return count($authorIds);
    }
}
