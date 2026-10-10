<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RevokeAchievement
{
    /**
     * An admin revokes an achievement with a mandatory reason, and it is logged. The row stays, so the achievement is
     * never granted again automatically, and if it unlocked the member's active title the title is removed.
     */
    public function handle(User $actor, UserAchievement $achievement, string $reason): UserAchievement
    {
        $member = User::query()->withTrashed()->findOrFail($achievement->user_id);

        Gate::forUser($actor)->authorize('manageAchievements', $member);

        $reason = trim($reason);

        throw_if($reason === '', ValidationException::withMessages(['reason' => __('admin.reason_required')]));

        return DB::transaction(function () use ($actor, $achievement, $member, $reason): UserAchievement {
            $locked = UserAchievement::query()->whereKey($achievement->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isRevoked()) {
                return $locked;
            }

            $locked->forceFill([
                'revoked_at' => now(),
                'revoked_by_id' => $actor->getKey(),
                'revoke_reason' => $reason,
            ])->save();

            $titleKey = $locked->achievement()?->titleKey();

            if ($titleKey !== null && $member->title_key === $titleKey) {
                $member->forceFill(['title_key' => null])->save();
            }

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::RevokeAchievement,
                'subject_type' => $member::class,
                'subject_id' => $member->getKey(),
                'reason' => $reason,
                'meta' => ['achievement' => $locked->achievement_key],
            ]);

            return $locked;
        });
    }
}
