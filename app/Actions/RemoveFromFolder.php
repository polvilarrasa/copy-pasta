<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\LimitsFolderChanges;
use App\Enums\AchievementMetric;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RemoveFromFolder
{
    use LimitsFolderChanges;

    public function __construct(
        private RecordEvent $recordEvent,
        private AdjustAchievementProgress $adjustProgress,
    ) {}

    /**
     * Removes the copy-pasta from the folder, lowering the favorites counter when the folder is the default one.
     * Returns false when the copy-pasta was not in the folder.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(User $user, Folder $folder, Copypasta $copypasta, array $context = []): bool
    {
        Gate::forUser($user)->authorize('removeCopypasta', $folder);
        $this->ensureFolderChangeIsAllowed($user);

        $removed = DB::transaction(function () use ($user, $folder, $copypasta): bool {
            // withTrashed(): a folder can hold a copy-pasta the author later deleted, shown as a placeholder, and
            // removing that placeholder must still work.
            $locked = Copypasta::withTrashed()->whereKey($copypasta->getKey())->lockForUpdate()->firstOrFail();

            if (! $folder->copypastas()->whereKey($locked->getKey())->exists()) {
                return false;
            }

            $folder->copypastas()->detach($locked->getKey());

            if ($folder->is_default) {
                $locked->forceFill(['favorites_count' => max(0, $locked->favorites_count - 1)])->save();

                $this->adjustProgress->add($user, AchievementMetric::Saved, -1);
            }

            return true;
        });

        if ($removed) {
            $this->recordEvent->handle(
                $folder->is_default ? EventType::FavoriteRemove : EventType::FolderRemove,
                $user,
                $copypasta,
                $context,
            );
        }

        return $removed;
    }
}
