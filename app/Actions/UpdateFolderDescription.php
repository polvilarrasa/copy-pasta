<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\LimitsFolderChanges;
use App\Models\Folder;
use App\Models\User;
use App\Support\UnicodeText;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

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

        $description = UnicodeText::cleanTitle((string) $description);

        throw_if(
            mb_strlen($description) > Folder::MAX_DESCRIPTION_LENGTH,
            ValidationException::withMessages(['description' => __('app.folders.errors.description_too_long', ['max' => Folder::MAX_DESCRIPTION_LENGTH])]),
        );

        $folder->update(['description' => $description !== '' ? $description : null]);

        return $folder;
    }
}
