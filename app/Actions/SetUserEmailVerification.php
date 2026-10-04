<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SetUserEmailVerification
{
    /**
     * Marks the member's address as verified or unverified by hand. A no-op when it already has that state.
     */
    public function handle(User $actor, User $target, bool $verified): User
    {
        Gate::forUser($actor)->authorize('verifyEmail', $target);

        if ($target->hasVerifiedEmail() === $verified) {
            return $target;
        }

        return DB::transaction(function () use ($actor, $target, $verified): User {
            $target->forceFill(['email_verified_at' => $verified ? now() : null])->save();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => $verified ? ModerationActionType::EmailVerified : ModerationActionType::EmailUnverified,
                'subject_type' => $target::class,
                'subject_id' => $target->getKey(),
            ]);

            return $target;
        });
    }
}
