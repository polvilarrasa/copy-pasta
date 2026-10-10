<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    public function view(User $user, Folder $folder): bool
    {
        return $user->getKey() === $folder->user_id;
    }

    /**
     * Any member who can sign in to the user panel sees their own folders.
     */
    public function viewAny(User $user): bool
    {
        return ! $user->isBanned();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Folder $folder): bool
    {
        return $user->getKey() === $folder->user_id && ! $folder->is_default;
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $user->getKey() === $folder->user_id && ! $folder->is_default;
    }

    /**
     * Only the owner adds to their folders, and only copy-pastas the public could see can be added.
     */
    public function addCopypasta(User $user, Folder $folder, Copypasta $copypasta): bool
    {
        return $user->getKey() === $folder->user_id
            && $copypasta->published_at !== null
            && ! $copypasta->isHidden();
    }

    /**
     * Staff take a public folder back to private; a private one has nothing to take back.
     */
    public function makePrivate(User $user, Folder $folder): bool
    {
        return $user->isStaff() && $folder->is_public;
    }

    public function removeCopypasta(User $user, Folder $folder): bool
    {
        return $user->getKey() === $folder->user_id;
    }
}
