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
    public function __construct(private RestoreCopypasta $restoreCopypasta) {}

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

                $this->restoreIfOrphaned($actor, $copypasta);
            }

            return $dismissed;
        });
    }

    /**
     * An automatic hide exists only because of its reports. Once they are all rejected, nothing keeps the copy-pasta
     * hidden and out of the queue, so it goes back up and the restore is logged.
     */
    private function restoreIfOrphaned(User $actor, Copypasta $copypasta): void
    {
        $copypasta->refresh();

        $hiddenAutomatically = $copypasta->isHidden() && $copypasta->hidden_by_id === null;
        $hasPendingReports = Report::query()->where('copypasta_id', $copypasta->getKey())->pending()->exists();

        if (! $hiddenAutomatically || $hasPendingReports) {
            return;
        }

        $this->restoreCopypasta->handle($actor, $copypasta);
    }
}
