<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Achievement;
use App\Models\User;
use App\Notifications\AchievementUnlockedNotification;
use Illuminate\Support\Facades\DB;

class GrantAchievement
{
    /**
     * Grants the achievement with an insert that ignores the unique (user, achievement) constraint, so it is
     * idempotent and a revoked achievement is never granted again. The notification goes out only when this call
     * actually inserted the row: two simultaneous evaluations cannot notify twice. Returns whether it inserted.
     */
    public function handle(User $user, Achievement $achievement, bool $notify = true): bool
    {
        $inserted = DB::table('user_achievements')->insertOrIgnore([
            'user_id' => $user->getKey(),
            'achievement_key' => $achievement->value,
            'unlocked_at' => now(),
        ]) === 1;

        if ($inserted && $notify) {
            $user->notify(new AchievementUnlockedNotification($achievement));
        }

        return $inserted;
    }
}
