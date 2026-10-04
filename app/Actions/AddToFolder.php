<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\LimitsFolderChanges;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AddToFolder
{
    use LimitsFolderChanges;

    /**
     * Adds the copy-pasta to the folder. Entries in the default folder are favorites, so they also move the counter.
     * Returns false when the copy-pasta was already in the folder.
     */
    public function handle(User $user, Folder $folder, Copypasta $copypasta): bool
    {
        Gate::forUser($user)->authorize('addCopypasta', [$folder, $copypasta]);
        $this->ensureFolderChangeIsAllowed($user);

        return DB::transaction(function () use ($folder, $copypasta): bool {
            $locked = Copypasta::query()->whereKey($copypasta->getKey())->lockForUpdate()->firstOrFail();

            if ($folder->copypastas()->whereKey($locked->getKey())->exists()) {
                return false;
            }

            $folder->copypastas()->attach($locked->getKey(), ['created_at' => now()]);

            if ($folder->is_default) {
                $locked->forceFill(['favorites_count' => $locked->favorites_count + 1])->save();
            }

            return true;
        });
    }
}
