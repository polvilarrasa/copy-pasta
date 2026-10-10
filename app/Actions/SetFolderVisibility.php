<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\LimitsFolderChanges;
use App\Enums\EventType;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SetFolderVisibility
{
    use LimitsFolderChanges;

    /**
     * Makes a folder public or private. Authorized by ownership alone (the `view` policy), so Favoritos can be made
     * public too, although every folder starts private. The first time a folder goes public it gets its public id; the
     * id is kept afterwards, so the same address works again when the owner shares it again. While private, that
     * address answers 404. A folder the staff made private is locked: the owner cannot make it public again until the
     * staff unlock it, and that is checked here, on the freshly read row, whatever the interface showed.
     */
    public function handle(User $user, Folder $folder, bool $public): Folder
    {
        Gate::forUser($user)->authorize('view', $folder);
        $this->ensureFolderChangeIsAllowed($user);

        $changed = false;

        $folder = DB::transaction(function () use ($user, $folder, $public, &$changed): Folder {
            $folder = Folder::query()->lockForUpdate()->findOrFail($folder->getKey());

            if ($public) {
                Gate::forUser($user)->authorize('publish', $folder);
            }

            if ((bool) $folder->is_public !== $public) {
                $folder->forceFill([
                    'is_public' => $public,
                    'public_id' => $folder->public_id ?? ($public ? (string) Str::ulid() : null),
                ])->save();

                $changed = true;
            }

            return $folder;
        });

        if ($changed) {
            app(RecordEvent::class)->handle(EventType::FolderVisibility, $user, null, ['public' => $public]);
        }

        return $folder;
    }
}
