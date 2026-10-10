<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RestoreUser
{
    public function __construct(private QueueAchievementEvaluation $queueEvaluation) {}

    /**
     * Brings back a soft-deleted member, who can sign in again with the same credentials.
     */
    public function handle(User $actor, User $target): void
    {
        Gate::forUser($actor)->authorize('restore', $target);

        if (! $target->trashed()) {
            return;
        }

        DB::transaction(function () use ($actor, $target): void {
            $target->restore();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::UserRestored,
                'subject_type' => $target::class,
                'subject_id' => $target->getKey(),
            ]);
        });

        $this->queueEvaluation->handle($target);
    }
}
