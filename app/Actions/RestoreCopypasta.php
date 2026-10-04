<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RestoreCopypasta
{
    /**
     * Make a hidden copy-pasta visible again and record the moderation action.
     */
    public function handle(User $actor, Copypasta $copypasta): Copypasta
    {
        Gate::forUser($actor)->authorize('restore', $copypasta);

        return DB::transaction(function () use ($actor, $copypasta): Copypasta {
            $copypasta->forceFill([
                'hidden_at' => null,
                'hidden_by_id' => null,
                'hidden_reason' => null,
            ])->save();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::Restore,
                'subject_type' => $copypasta::class,
                'subject_id' => $copypasta->getKey(),
            ]);

            return $copypasta;
        });
    }
}
