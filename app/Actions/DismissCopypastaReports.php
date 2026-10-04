<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Enums\ReportStatus;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DismissCopypastaReports
{
    /**
     * Rejects every pending report on the copy-pasta and logs the decision. Returns how many were dismissed.
     */
    public function handle(User $actor, Copypasta $copypasta): int
    {
        Gate::forUser($actor)->authorize('dismissReports', $copypasta);

        return DB::transaction(function () use ($actor, $copypasta): int {
            $dismissed = Report::query()
                ->where('copypasta_id', $copypasta->getKey())
                ->pending()
                ->update([
                    'status' => ReportStatus::Rejected->value,
                    'resolved_by_id' => $actor->getKey(),
                    'resolved_at' => now(),
                ]);

            if ($dismissed > 0) {
                ModerationAction::query()->create([
                    'actor_id' => $actor->getKey(),
                    'action' => ModerationActionType::DismissReports,
                    'subject_type' => $copypasta::class,
                    'subject_id' => $copypasta->getKey(),
                ]);
            }

            return $dismissed;
        });
    }
}
