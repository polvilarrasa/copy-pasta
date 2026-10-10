<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AchievementMetric;
use App\Enums\FeedSort;
use App\Queries\FeedQuery;

class GrantTrendingAchievements
{
    public const TOP = 10;

    public function __construct(
        private AdjustAchievementProgress $adjustProgress,
        private QueueAchievementEvaluation $queueEvaluation,
    ) {}

    /**
     * Raises the Trending flag of the authors in the weekly top 10: the same order as the public "top semana" feed,
     * without NSFW and only copy-pastas with a positive score. Returns the ids of the authors.
     *
     * @return list<int>
     */
    public function handle(): array
    {
        $authorIds = $this->authorIds();

        foreach ($authorIds as $authorId) {
            $this->adjustProgress->raiseTo($authorId, AchievementMetric::TrendingTop, 1);
            $this->queueEvaluation->handle($authorId, [AchievementMetric::TrendingTop]);
        }

        return $authorIds;
    }

    /**
     * The authors of the weekly top 10.
     *
     * @return list<int>
     */
    public function authorIds(): array
    {
        /** @var list<int> $authorIds */
        $authorIds = FeedQuery::make()
            ->sort(FeedSort::TopWeek)
            ->nsfw(false)
            ->builder()
            ->where('score', '>', 0)
            ->limit(self::TOP)
            ->pluck('user_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $authorIds;
    }
}
