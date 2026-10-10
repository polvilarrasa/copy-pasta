<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\EvaluateUserAchievements;
use App\Enums\AchievementMetric;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Evaluates the achievements that read the metrics an action just touched. The progress is already written when this
 * runs; the job only reads it, grants and notifies.
 */
class EvaluateUserAchievementsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $metrics  AchievementMetric values
     */
    public function __construct(public int $userId, public array $metrics)
    {
        $this->afterCommit();
    }

    public function handle(EvaluateUserAchievements $evaluate): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            return;
        }

        $evaluate->handle($user, array_map(AchievementMetric::from(...), $this->metrics));
    }
}
