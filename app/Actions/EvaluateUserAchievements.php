<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Achievement;
use App\Enums\AchievementMetric;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EvaluateUserAchievements
{
    public function __construct(private GrantAchievement $grantAchievement) {}

    /**
     * Grants every achievement on the given metrics whose threshold the member's progress has reached. It reads the
     * progress table (and, for derived metrics, the user row); it never counts events or votes. Banned, deleted and
     * anonymized accounts are not evaluated. Returns the achievements granted by this call.
     *
     * @param  list<AchievementMetric>  $metrics
     * @return list<Achievement>
     */
    public function handle(User $user, array $metrics): array
    {
        if (! $user->canEarnAchievements()) {
            return [];
        }

        $candidates = Achievement::forMetrics($metrics);

        if ($candidates === []) {
            return [];
        }

        $stored = DB::table('user_achievement_progress')
            ->where('user_id', $user->getKey())
            ->whereIn('metric', array_map(fn (AchievementMetric $metric): string => $metric->value, $metrics))
            ->pluck('value', 'metric');

        $known = DB::table('user_achievements')->where('user_id', $user->getKey())->pluck('achievement_key')->all();

        $granted = [];

        foreach ($candidates as $achievement) {
            if (in_array($achievement->value, $known, true)) {
                continue;
            }

            $metric = $achievement->metric();
            $value = $metric->isDerived() ? $metric->derivedValue($user) : (int) ($stored[$metric->value] ?? 0);

            if ($value >= $achievement->threshold() && $this->grantAchievement->handle($user, $achievement)) {
                $granted[] = $achievement;
            }
        }

        return $granted;
    }
}
