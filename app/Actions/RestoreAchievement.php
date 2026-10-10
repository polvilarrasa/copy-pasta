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

class RestoreAchievement
{
    /**
     * An admin gives a revoked achievement back, with a mandatory reason, and it is logged. The member is not
     * notified and the title is not set again on its own.
     */
    public function handle(User $actor, UserAchievement $achievement, string $reason): UserAchievement
    {
        $member = User::query()->withTrashed()->findOrFail($achievement->user_id);

        Gate::forUser($actor)->authorize('manageAchievements', $member);

        $reason = trim($reason);

        throw_if($reason === '', ValidationException::withMessages(['reason' => __('admin.reason_required')]));

        return DB::transaction(function () use ($actor, $achievement, $member, $reason): UserAchievement {
            $locked = UserAchievement::query()->whereKey($achievement->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isRevoked()) {
                return $locked;
            }

            $locked->forceFill(['revoked_at' => null, 'revoked_by_id' => null, 'revoke_reason' => null])->save();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::RestoreAchievement,
                'subject_type' => $member::class,
                'subject_id' => $member->getKey(),
                'reason' => $reason,
                'meta' => ['achievement' => $locked->achievement_key],
            ]);

            return $locked;
        });
    }
}
