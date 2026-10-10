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
    public function __construct(private QueueAchievementEvaluation $queueEvaluation) {}

    /**
     * Lifts the ban and evaluates all the member's achievements, so what they met while banned is granted now.
     */
    public function handle(User $actor, User $target): User
    {
        Gate::forUser($actor)->authorize('unban', $target);

        $target = DB::transaction(function () use ($actor, $target): User {
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

        $this->queueEvaluation->handle($target);

        return $target;
    }
}
