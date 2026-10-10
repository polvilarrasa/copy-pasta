<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Achievement;
use App\Enums\AchievementMetric;
use App\Models\User;

class GrantVeteranAchievements
{
    public function __construct(private QueueAchievementEvaluation $queueEvaluation) {}

    /**
     * Queues the evaluation of every active account old enough for Veterano that does not hold it yet. Returns how
     * many accounts were queued.
     */
    public function handle(): int
    {
        $queued = 0;

        User::query()
            ->whereNull('banned_at')
            ->whereNull('anonymized_at')
            ->where('created_at', '<=', now()->subDays(Achievement::Veteran->threshold()))
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('user_achievements')
                ->whereColumn('user_achievements.user_id', 'users.id')
                ->where('user_achievements.achievement_key', Achievement::Veteran->value))
            ->select('id')
            ->each(function (User $user) use (&$queued): void {
                $this->queueEvaluation->handle($user, [AchievementMetric::AccountDays]);
                $queued++;
            });

        return $queued;
    }
}
