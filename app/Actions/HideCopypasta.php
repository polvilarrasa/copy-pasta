<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class HideCopypasta
{
    /**
     * Hide a copy-pasta from public listings and record the moderation action.
     */
    public function handle(User $actor, Copypasta $copypasta, string $reason): Copypasta
    {
        Gate::forUser($actor)->authorize('hide', $copypasta);

        $reason = trim($reason);

        throw_if(
            $reason === '',
            ValidationException::withMessages(['reason' => __('admin.reason_required')]),
        );

        return DB::transaction(function () use ($actor, $copypasta, $reason): Copypasta {
            $copypasta->forceFill([
                'hidden_at' => now(),
                'hidden_by_id' => $actor->getKey(),
                'hidden_reason' => $reason,
            ])->save();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::Hide,
                'subject_type' => $copypasta::class,
                'subject_id' => $copypasta->getKey(),
                'reason' => $reason,
            ]);

            return $copypasta;
        });
    }
}
