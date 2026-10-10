<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\LimitsFolderChanges;
use App\Enums\EventType;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SetFolderVisibility
{
    use LimitsFolderChanges;

    /**
     * Makes a folder public or private. Authorized by ownership alone (the `view` policy), so Favoritos can be made
     * public too, although every folder starts private. The first time a folder goes public it gets its public id; the
     * id is kept afterwards, so the same address works again when the owner shares it again. While private, that
     * address answers 404.
     */
    public function handle(User $user, Folder $folder, bool $public): Folder
    {
        Gate::forUser($user)->authorize('view', $folder);
        $this->ensureFolderChangeIsAllowed($user);

        if ((bool) $folder->is_public === $public) {
            return $folder;
        }

        $folder->forceFill([
            'is_public' => $public,
            'public_id' => $folder->public_id ?? ($public ? (string) Str::ulid() : null),
        ])->save();

        app(RecordEvent::class)->handle(EventType::FolderVisibility, $user, null, ['public' => $public]);

        return $folder;
    }
}
