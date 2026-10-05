<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Mail\ReportedMinorAlertMail;
use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    public function __construct(
        private ConcealCopypasta $concealCopypasta,
        private SaveCopypastaRevision $saveCopypastaRevision,
        private RecordEvent $recordEvent,
    ) {}

    /**
     * Records a member's report. Its weight depends on the reporter, and the copy-pasta is hidden automatically when
     * the pending weight reaches the threshold. A report about minors hides the copy-pasta on its own only when a
     * trusted member or staff makes it. Otherwise it goes to the top of the queue and alerts the admins.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(User $reporter, Copypasta $copypasta, ReportReason $reason, ?string $details, array $context = []): Report
    {
        Gate::forUser($reporter)->authorize('create', [Report::class, $copypasta]);

        $details = $this->validatedDetails($reason, $details);

        throw_unless(
            RateLimiter::attempt('reports:'.$reporter->getKey(), self::MAX_REPORTS_PER_HOUR, fn (): bool => true, 3600),
            new ThrottleRequestsException(__('moderation.errors.rate_limited')),
        );

        $report = $this->record(
            $copypasta,
            $reason,
            $details,
            $reporter->isTrusted() ? Report::TRUSTED_WEIGHT : Report::DEFAULT_WEIGHT,
            $reporter,
            contactEmail: null,
        );

        $this->hideIfNeeded($report, canHideForMinors: $reporter->isTrusted() || $reporter->isStaff());

        if ($report->reason === ReportReason::SexualContentMinors) {
            $this->alertAdmins($report);
        }

        $this->recordEvent->handle(EventType::Report, $reporter, $copypasta, [...$context, 'reason' => $reason->value]);

        return $report;
    }

    /**
     * A notice from a visitor who is not signed in. It weighs as an ordinary report, keeps a contact address for the
     * reply, and never hides a copy-pasta about minors on its own.
     */
    public function handleAnonymous(string $contactEmail, Copypasta $copypasta, ReportReason $reason, ?string $details): Report
    {
        throw_unless(
            $copypasta->published_at !== null && ! $copypasta->isHidden(),
            new ModelNotFoundException,
        );

        $details = $this->validatedDetails($reason, $details);

        $report = $this->record($copypasta, $reason, $details, Report::DEFAULT_WEIGHT, reporter: null, contactEmail: $contactEmail);

        $this->hideIfNeeded($report, canHideForMinors: false);

        if ($report->reason === ReportReason::SexualContentMinors) {
            $this->alertAdmins($report);
        }

        $this->recordEvent->handle(EventType::Report, null, $copypasta, ['reason' => $reason->value]);

        return $report;
    }

    /**
     * The report keeps the version of the copy-pasta the reporter saw, so the queue can show what was reported.
     */
    private function record(
        Copypasta $copypasta,
        ReportReason $reason,
        ?string $details,
        int $weight,
        ?User $reporter,
        ?string $contactEmail,
    ): Report {
        return DB::transaction(function () use ($copypasta, $reason, $details, $weight, $reporter, $contactEmail): Report {
            $locked = Copypasta::query()->whereKey($copypasta->getKey())->lockForUpdate()->firstOrFail();

            if ($reporter !== null) {
                $alreadyReported = Report::query()
                    ->where('copypasta_id', $locked->getKey())
                    ->where('reporter_id', $reporter->getKey())
                    ->pending()
                    ->exists();

                throw_if(
                    $alreadyReported,
                    ValidationException::withMessages(['reason' => __('moderation.errors.duplicate_pending')]),
                );
            }

            return Report::query()->create([
                'copypasta_id' => $locked->getKey(),
                'copypasta_revision_id' => $this->saveCopypastaRevision->currentFor($locked)->getKey(),
                'reporter_id' => $reporter?->getKey(),
                'contact_email' => $contactEmail,
                'reason' => $reason,
                'weight' => $weight,
                'details' => $details,
                'status' => ReportStatus::Pending,
            ]);
        });
    }

    private function hideIfNeeded(Report $report, bool $canHideForMinors): void
    {
        $copypasta = $report->copypasta()->firstOrFail();

        if ($copypasta->isHidden()) {
            return;
        }

        $pendingWeight = (int) Report::query()->where('copypasta_id', $copypasta->getKey())->pending()->sum('weight');

        $reachedThreshold = $pendingWeight >= Report::AUTO_HIDE_THRESHOLD;
        $hidesForMinors = $report->reason === ReportReason::SexualContentMinors && $canHideForMinors;

        if ($reachedThreshold || $hidesForMinors) {
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

    private function validatedDetails(ReportReason $reason, ?string $details): ?string
    {
        $details = filled($details) ? trim((string) $details) : null;

        throw_if(
            $reason === ReportReason::Other && ! $this->hasValidDetails($details),
            ValidationException::withMessages(['details' => __('moderation.errors.details_length')]),
        );

        return $details;
    }

    private function hasValidDetails(?string $details): bool
    {
        return $details !== null
            && mb_strlen($details) >= self::DETAILS_MIN_LENGTH
            && mb_strlen($details) <= self::DETAILS_MAX_LENGTH;
    }
}
