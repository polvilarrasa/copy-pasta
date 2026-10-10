<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\LimitsFolderChanges;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateFolderDescription
{
    use LimitsFolderChanges;

    /**
     * Sets a folder's description. Gated by ownership alone (the `view` policy), not `update`, so the protected
     * default folder can have a description even though it cannot be renamed or deleted.
     */
    public function handle(User $user, Folder $folder, ?string $description): Folder
    {
        Gate::forUser($user)->authorize('view', $folder);
        $this->ensureFolderChangeIsAllowed($user);

        $folder->update(['description' => $description !== null && $description !== '' ? $description : null]);

        return $folder;
    }
}
