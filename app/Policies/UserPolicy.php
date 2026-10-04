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

    /**
     * Soft-deleted members cannot be edited; restoring them comes first.
     */
    public function update(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $target->trashed();
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

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $target): bool
    {
        return $this->ban($user, $target);
    }

    /**
     * An anonymized account lost its identity and cannot be brought back.
     */
    public function restore(User $user, User $target): bool
    {
        return $this->ban($user, $target) && ! $target->isAnonymized();
    }

    /**
     * Members delete only their own account, and only while it is still active.
     */
    public function deleteOwnAccount(User $user, User $target): bool
    {
        return $user->is($target) && ! $target->isAnonymized();
    }

    public function verifyEmail(User $user, User $target): bool
    {
        return $this->ban($user, $target);
    }

    public function resendVerification(User $user, User $target): bool
    {
        return $this->ban($user, $target);
    }
}
