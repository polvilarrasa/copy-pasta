<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteUser
{
    /**
     * Soft-deletes a member. They cannot sign in afterwards, and their sessions end on the next request. Their
     * copy-pastas and reports stay in place, and the account can be restored.
     */
    public function handle(User $actor, User $target): void
    {
        Gate::forUser($actor)->authorize('delete', $target);

        if ($target->trashed()) {
            return;
        }

        DB::transaction(function () use ($actor, $target): void {
            $target->delete();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::UserDeleted,
                'subject_type' => $target::class,
                'subject_id' => $target->getKey(),
            ]);
        });
    }
}
