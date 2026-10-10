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

    /**
     * Lists the member's own copy-pastas. An unverified member sees an empty list until they can publish.
     */
    public function viewOwn(User $user): bool
    {
        return ! $user->isBanned();
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

    public function dismissReports(User $user, Copypasta $copypasta): bool
    {
        return $user->isStaff();
    }

    public function vote(User $user, Copypasta $copypasta): bool
    {
        return $this->canEngage($user, $copypasta);
    }

    public function favorite(User $user, Copypasta $copypasta): bool
    {
        return $this->canEngage($user, $copypasta);
    }

    /**
     * "No me interesa" applies to what the member could vote on: visible and not their own.
     */
    public function dismiss(User $user, Copypasta $copypasta): bool
    {
        return $this->canEngage($user, $copypasta);
    }

    /**
     * Staff pick the copy-pasta of the day among the visible ones that are not adult content.
     */
    public function feature(User $user, Copypasta $copypasta): bool
    {
        return $user->isStaff()
            && $copypasta->published_at !== null
            && ! $copypasta->isHidden()
            && $copypasta->deleted_at === null
            && ! $copypasta->is_nsfw;
    }

    /**
     * Votes and favorites need a visible copy-pasta that is not the user's own. Unverified members may engage.
     */
    private function canEngage(User $user, Copypasta $copypasta): bool
    {
        return $copypasta->published_at !== null
            && ! $copypasta->isHidden()
            && $user->getKey() !== $copypasta->user_id;
    }
}
