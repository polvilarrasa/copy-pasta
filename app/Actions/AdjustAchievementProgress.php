<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AchievementMetric;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdjustAchievementProgress
{
    /**
     * Moves a counter metric by a delta with one atomic upsert, so two requests never lose an update. The value never
     * goes below zero. Call it inside the transaction of the action that moves the metric: if the action rolls back,
     * so does the progress.
     */
    public function add(User|int $user, AchievementMetric $metric, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        DB::statement(
            'INSERT INTO user_achievement_progress (user_id, metric, value) VALUES (?, ?, ?) '
            .'ON CONFLICT (user_id, metric) DO UPDATE '
            .'SET value = GREATEST(0, user_achievement_progress.value + ?)',
            [$this->id($user), $metric->value, max(0, $delta), $delta],
        );
    }

    /**
     * Raises a metric to at least the given value; it never lowers it. Used for flags.
     */
    public function raiseTo(User|int $user, AchievementMetric $metric, int $value): void
    {
        DB::statement(
            'INSERT INTO user_achievement_progress (user_id, metric, value) VALUES (?, ?, ?) '
            .'ON CONFLICT (user_id, metric) DO UPDATE '
            .'SET value = GREATEST(user_achievement_progress.value, ?)',
            [$this->id($user), $metric->value, $value, $value],
        );
    }

    private function id(User|int $user): int
    {
        return $user instanceof User ? (int) $user->getKey() : $user;
    }
}
