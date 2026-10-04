<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RemoveFromFolder
{
    /**
     * Removes the copy-pasta from the folder, lowering the favorites counter when the folder is the default one.
     * Returns false when the copy-pasta was not in the folder.
     */
    public function handle(User $user, Folder $folder, Copypasta $copypasta): bool
    {
        Gate::forUser($user)->authorize('removeCopypasta', $folder);

        return DB::transaction(function () use ($folder, $copypasta): bool {
            $locked = Copypasta::query()->whereKey($copypasta->getKey())->lockForUpdate()->firstOrFail();

            if (! $folder->copypastas()->whereKey($locked->getKey())->exists()) {
                return false;
            }

            $folder->copypastas()->detach($locked->getKey());

            if ($folder->is_default) {
                $locked->forceFill(['favorites_count' => max(0, $locked->favorites_count - 1)])->save();
            }

            return true;
        });
    }
}
