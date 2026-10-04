<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Mail\ReportedMinorAlertMail;
use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ReportCopypasta
{
    public const MAX_REPORTS_PER_HOUR = 10;

    private const DETAILS_MIN_LENGTH = 10;

    private const DETAILS_MAX_LENGTH = 500;

    public function __construct(private ConcealCopypasta $concealCopypasta) {}

    /**
     * Records a report and hides the copy-pasta automatically when the threshold is reached or the reason
     * concerns minors. Only one pending report per member and copy-pasta is allowed.
     */
    public function handle(User $reporter, Copypasta $copypasta, ReportReason $reason, ?string $details): Report
    {
        Gate::forUser($reporter)->authorize('create', [Report::class, $copypasta]);

        $details = filled($details) ? trim((string) $details) : null;

        throw_if(
            $reason === ReportReason::Other && ! $this->hasValidDetails($details),
            ValidationException::withMessages(['details' => __('moderation.errors.details_length')]),
        );

        throw_unless(
            RateLimiter::attempt('reports:'.$reporter->getKey(), self::MAX_REPORTS_PER_HOUR, fn (): bool => true, 3600),
            new ThrottleRequestsException(__('moderation.errors.rate_limited')),
        );

        $report = DB::transaction(function () use ($reporter, $copypasta, $reason, $details): Report {
            $locked = Copypasta::query()->whereKey($copypasta->getKey())->lockForUpdate()->firstOrFail();

            $alreadyReported = Report::query()
                ->where('copypasta_id', $locked->getKey())
                ->where('reporter_id', $reporter->getKey())
                ->pending()
                ->exists();

            throw_if(
                $alreadyReported,
                ValidationException::withMessages(['reason' => __('moderation.errors.duplicate_pending')]),
            );

            return Report::query()->create([
                'copypasta_id' => $locked->getKey(),
                'reporter_id' => $reporter->getKey(),
                'reason' => $reason,
                'details' => $details,
                'status' => ReportStatus::Pending,
            ]);
        });

        $this->hideIfNeeded($report);

        if ($report->reason === ReportReason::SexualContentMinors) {
            $this->alertAdmins($report);
        }

        return $report;
    }

    private function hideIfNeeded(Report $report): void
    {
        $copypasta = $report->copypasta()->firstOrFail();

        $pendingReports = Report::query()->where('copypasta_id', $copypasta->getKey())->pending()->count();

        $reachedThreshold = $pendingReports >= Report::AUTO_HIDE_THRESHOLD;
        $aboutMinors = $report->reason === ReportReason::SexualContentMinors;

        if (! $copypasta->isHidden() && ($reachedThreshold || $aboutMinors)) {
            $this->concealCopypasta->handle(null, $copypasta, __('moderation.auto_hidden_reason'), acceptsPendingReports: false);
        }
    }

    private function alertAdmins(Report $report): void
    {
        $admins = User::query()->verifiedAdmins()->get();

        if ($admins->isEmpty()) {
            return;
        }

        Mail::to($admins)->queue(new ReportedMinorAlertMail($report));
    }

    private function hasValidDetails(?string $details): bool
    {
        return $details !== null
            && mb_strlen($details) >= self::DETAILS_MIN_LENGTH
            && mb_strlen($details) <= self::DETAILS_MAX_LENGTH;
    }
}
