<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RenameFolder
{
    /**
     * Renames a folder. The default folder is protected by the policy, so Favoritos keeps its name.
     */
    public function handle(User $user, Folder $folder, string $name): Folder
    {
        Gate::forUser($user)->authorize('update', $folder);

        $name = trim($name);

        $nameTaken = Folder::query()
            ->where('user_id', $folder->user_id)
            ->where('name', $name)
            ->whereKeyNot($folder->getKey())
            ->exists();

        if ($nameTaken) {
            throw ValidationException::withMessages(['name' => __('app.folders.errors.name_taken')]);
        }

        $folder->update(['name' => $name]);

        return $folder;
    }
}
