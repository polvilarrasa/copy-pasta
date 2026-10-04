<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Only verified members report visible copy-pastas that are not their own.
     */
    public function create(User $user, Copypasta $copypasta): bool
    {
        return $user->hasVerifiedEmail()
            && $user->getKey() !== $copypasta->user_id
            && $copypasta->published_at !== null
            && ! $copypasta->isHidden();
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
