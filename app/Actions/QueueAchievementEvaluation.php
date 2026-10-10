<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AchievementMetric;
use App\Jobs\EvaluateUserAchievementsJob;
use App\Models\User;

class QueueAchievementEvaluation
{
    /**
     * Queues the evaluation of the member's achievements for the metrics that were touched, once the transaction that
     * touched them commits. Without metrics it evaluates all of them.
     *
     * @param  list<AchievementMetric>|null  $metrics
     */
    public function handle(User|int $user, ?array $metrics = null): void
    {
        $metrics ??= AchievementMetric::cases();

        EvaluateUserAchievementsJob::dispatch(
            $user instanceof User ? (int) $user->getKey() : $user,
            array_values(array_unique(array_map(fn (AchievementMetric $metric): string => $metric->value, $metrics))),
        );
    }
}
