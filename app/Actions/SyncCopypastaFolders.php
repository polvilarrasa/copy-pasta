<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SyncCopypastaFolders
{
    public function __construct(
        private AddToFolder $addToFolder,
        private RemoveFromFolder $removeFromFolder,
    ) {}

    /**
     * Makes the set of the member's folders that hold the copy-pasta equal to the given ids. Ids of folders the
     * member does not own are ignored, so one request cannot touch someone else's collections.
     *
     * @param  array<int, int|string>  $folderIds
     */
    public function handle(User $user, Copypasta $copypasta, array $folderIds): void
    {
        $targetIds = Folder::query()
            ->where('user_id', $user->getKey())
            ->whereIn('id', array_map('intval', $folderIds))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $currentIds = Folder::query()
            ->where('user_id', $user->getKey())
            ->whereHas('copypastas', fn ($query) => $query->whereKey($copypasta->getKey()))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        DB::transaction(function () use ($user, $copypasta, $targetIds, $currentIds): void {
            $toAdd = array_diff($targetIds, $currentIds);
            $toRemove = array_diff($currentIds, $targetIds);

            foreach (Folder::query()->whereKey($toAdd)->get() as $folder) {
                $this->addToFolder->handle($user, $folder, $copypasta);
            }

            foreach (Folder::query()->whereKey($toRemove)->get() as $folder) {
                $this->removeFromFolder->handle($user, $folder, $copypasta);
            }
        });
    }
}
