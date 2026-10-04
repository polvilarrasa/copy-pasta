<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BanUser
{
    /**
     * Bans a member with a mandatory reason. Their open sessions end on the next request.
     */
    public function handle(User $actor, User $target, string $reason): User
    {
        Gate::forUser($actor)->authorize('ban', $target);

        $reason = trim($reason);

        throw_if(
            $reason === '',
            ValidationException::withMessages(['reason' => __('admin.reason_required')]),
        );

        return DB::transaction(function () use ($actor, $target, $reason): User {
            $target->forceFill([
                'banned_at' => now(),
                'ban_reason' => $reason,
            ])->save();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::Ban,
                'subject_type' => $target::class,
                'subject_id' => $target->getKey(),
                'reason' => $reason,
            ]);

            return $target;
        });
    }
}
