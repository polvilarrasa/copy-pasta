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

    public function sendPasswordReset(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins act on anyone but themselves; a staff member cannot ban or change their own role.
     */
    public function ban(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $user->is($target);
    }

    public function unban(User $user, User $target): bool
    {
        return $this->ban($user, $target);
    }

    public function changeRole(User $user, User $target): bool
    {
        return $this->ban($user, $target);
    }

    public function impersonate(User $user, User $target): bool
    {
        return $user->isAdmin()
            && $target->canBeImpersonated()
            && ! $user->is($target);
    }
}
