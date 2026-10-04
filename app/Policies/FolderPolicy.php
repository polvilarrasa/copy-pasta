<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    public function view(User $user, Folder $folder): bool
    {
        return $user->is($folder->user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Folder $folder): bool
    {
        return $user->is($folder->user) && ! $folder->is_default;
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $user->is($folder->user) && ! $folder->is_default;
    }
}
