<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /** Accounts younger than this cannot report, and neither can members with too many rejected reports. */
    public const ACCOUNT_AGE_HOURS = 72;

    public const REJECTIONS_LIMIT = 3;

    public const REJECTIONS_WINDOW_DAYS = 30;

    /**
     * Only verified, established members report visible copy-pastas that are not their own. Staff are not held to the
     * account age, so a newly created moderator can report at once.
     */
    public function create(User $user, Copypasta $copypasta): bool
    {
        return $user->hasVerifiedEmail()
            && ($user->isStaff() || $user->created_at->lte(now()->subHours(self::ACCOUNT_AGE_HOURS)))
            && ! $this->reportingIsSuspended($user)
            && $user->getKey() !== $copypasta->user_id
            && $copypasta->published_at !== null
            && ! $copypasta->isHidden();
    }

    /**
     * Members who keep reporting things moderators reject lose the right for a while. It returns as those rejections age
     * past the window.
     */
    private function reportingIsSuspended(User $user): bool
    {
        return Report::query()
            ->where('reporter_id', $user->getKey())
            ->where('status', ReportStatus::Rejected)
            ->where('resolved_at', '>=', now()->subDays(self::REJECTIONS_WINDOW_DAYS))
            ->count() >= self::REJECTIONS_LIMIT;
    }

    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Report $report): bool
    {
        return $user->isStaff();
    }

    public function resolve(User $user, Report $report): bool
    {
        return $user->isStaff();
    }
}
