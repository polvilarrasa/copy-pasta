<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteFolder
{
    /**
     * Deletes a folder and its entries. Copy-pastas themselves are untouched, and the default folder is protected.
     */
    public function handle(User $user, Folder $folder): void
    {
        Gate::forUser($user)->authorize('delete', $folder);

        $folder->delete();
    }
}
