<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function impersonate(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $target->isStaff() && ! $user->is($target);
    }
}
