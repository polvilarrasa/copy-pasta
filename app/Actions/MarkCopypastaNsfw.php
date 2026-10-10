<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Enums\ReportReason;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MarkCopypastaNsfw
{
    public function __construct(private AcceptCopypastaReports $acceptReports) {}

    /**
     * Set or clear the NSFW flag. Changes are logged only when the flag actually changes. Marking a copy-pasta as NSFW
     * proves the "NSFW not marked" reports on it right, so they are accepted (and their reporters notified); the count
     * goes in the log entry.
     */
    public function handle(User $actor, Copypasta $copypasta, bool $isNsfw): Copypasta
    {
        Gate::forUser($actor)->authorize('markNsfw', $copypasta);

        if ($copypasta->is_nsfw === $isNsfw) {
            return $copypasta;
        }

        return DB::transaction(function () use ($actor, $copypasta, $isNsfw): Copypasta {
            $copypasta->forceFill(['is_nsfw' => $isNsfw])->save();

            $acceptedReports = $isNsfw
                ? $this->acceptReports->handle($actor, $copypasta, ReportReason::NsfwUnmarked)
                : 0;

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => $isNsfw ? ModerationActionType::MarkNsfw : ModerationActionType::UnmarkNsfw,
                'subject_type' => $copypasta::class,
                'subject_id' => $copypasta->getKey(),
                'meta' => $acceptedReports > 0 ? ['accepted_reports' => $acceptedReports] : null,
            ]);

            return $copypasta;
        });
    }
}
