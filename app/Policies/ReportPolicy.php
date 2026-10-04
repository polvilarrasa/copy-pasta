<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function create(User $user, Copypasta $copypasta): bool
    {
        return $user->hasVerifiedEmail() && ! $user->is($copypasta->user);
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
