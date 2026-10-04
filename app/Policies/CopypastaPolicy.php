<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Copypasta;
use App\Models\User;

class CopypastaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(?User $user, Copypasta $copypasta): bool
    {
        if ($copypasta->published_at !== null && ! $copypasta->isHidden()) {
            return true;
        }

        return $user !== null && ($user->is($copypasta->user) || $user->isStaff());
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, Copypasta $copypasta): bool
    {
        return $user->is($copypasta->user);
    }

    public function delete(User $user, Copypasta $copypasta): bool
    {
        return $user->is($copypasta->user);
    }

    public function hide(User $user, Copypasta $copypasta): bool
    {
        return $user->isStaff();
    }

    public function restore(User $user, Copypasta $copypasta): bool
    {
        return $user->isStaff();
    }

    public function markNsfw(User $user, Copypasta $copypasta): bool
    {
        return $user->isStaff();
    }
}
