<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AchievementMetric;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportAcceptedNotification;
use Illuminate\Support\Facades\DB;

class AcceptCopypastaReports
{
    public function __construct(
        private AdjustAchievementProgress $adjustProgress,
        private QueueAchievementEvaluation $queueEvaluation,
    ) {}

    /**
     * Accepts the pending reports on the copy-pasta, or only those with the given reason, and tells each member who
     * made one once the transaction commits. Anonymous notices have nobody to notify. Returns how many were accepted.
     */
    public function handle(?User $actor, Copypasta $copypasta, ?ReportReason $reason = null): int
    {
        $pending = Report::query()
            ->where('copypasta_id', $copypasta->getKey())
            ->pending()
            ->when($reason !== null, fn ($query) => $query->where('reason', $reason));

        $reporterIds = (clone $pending)->whereNotNull('reporter_id')->distinct()->pluck('reporter_id')->all();

        $accepted = DB::transaction(function () use ($pending, $actor, $reporterIds): int {
            $accepted = $pending->update([
                'status' => ReportStatus::Accepted->value,
                'resolved_by_id' => $actor?->getKey(),
                'resolved_at' => now(),
            ]);

            foreach ($reporterIds as $reporterId) {
                $this->adjustProgress->add((int) $reporterId, AchievementMetric::ReportsAccepted, 1);
            }

            return $accepted;
        });

        foreach ($reporterIds as $reporterId) {
            $this->queueEvaluation->handle((int) $reporterId, [AchievementMetric::ReportsAccepted]);
        }

        if ($reporterIds !== []) {
            DB::afterCommit(function () use ($reporterIds, $copypasta): void {
                User::query()->whereIn('id', $reporterIds)->each(
                    fn (User $reporter) => $reporter->notify(new ReportAcceptedNotification($copypasta)),
                );
            });
        }

        return $accepted;
    }
}
