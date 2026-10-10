<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Enums\Role;
use App\Models\ModerationAction;
use App\Models\User;
use App\Notifications\TrustedPromotionNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChangeUserRole
{
    /**
     * Sets the member's role and logs the previous and new values. A no-op when the role does not change. A member
     * promoted to trusted is notified; moving staff down to trusted is not a promotion.
     */
    public function handle(User $actor, User $target, Role $role): User
    {
        Gate::forUser($actor)->authorize('changeRole', $target);

        if ($target->role === $role) {
            return $target;
        }

        $previous = $target->role;

        $target = DB::transaction(function () use ($actor, $target, $role, $previous): User {
            $target->forceFill(['role' => $role])->save();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::ChangeRole,
                'subject_type' => $target::class,
                'subject_id' => $target->getKey(),
                'meta' => ['from' => $previous->value, 'to' => $role->value],
            ]);

            return $target;
        });

        if ($role === Role::Trusted && $previous === Role::User) {
            $target->notify(new TrustedPromotionNotification);
        }

        return $target;
    }
}
