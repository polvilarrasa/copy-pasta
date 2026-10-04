<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MarkCopypastaNsfw
{
    /**
     * Set or clear the NSFW flag. Changes are logged only when the flag actually changes.
     */
    public function handle(User $actor, Copypasta $copypasta, bool $isNsfw): Copypasta
    {
        Gate::forUser($actor)->authorize('markNsfw', $copypasta);

        if ($copypasta->is_nsfw === $isNsfw) {
            return $copypasta;
        }

        return DB::transaction(function () use ($actor, $copypasta, $isNsfw): Copypasta {
            $copypasta->forceFill(['is_nsfw' => $isNsfw])->save();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => $isNsfw ? ModerationActionType::MarkNsfw : ModerationActionType::UnmarkNsfw,
                'subject_type' => $copypasta::class,
                'subject_id' => $copypasta->getKey(),
            ]);

            return $copypasta;
        });
    }
}
