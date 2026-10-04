<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UnbanUser
{
    public function handle(User $actor, User $target): User
    {
        Gate::forUser($actor)->authorize('unban', $target);

        return DB::transaction(function () use ($actor, $target): User {
            $target->forceFill([
                'banned_at' => null,
                'ban_reason' => null,
            ])->save();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::Unban,
                'subject_type' => $target::class,
                'subject_id' => $target->getKey(),
            ]);

            return $target;
        });
    }
}
