<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\Folder;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MakeFolderPrivate
{
    /**
     * Staff take a public folder back to private, with a mandatory reason. The change is logged with the owner and the
     * name the folder had, since the owner can rename it afterwards.
     */
    public function handle(User $actor, Folder $folder, string $reason): Folder
    {
        Gate::forUser($actor)->authorize('makePrivate', $folder);

        $reason = trim($reason);

        throw_if(
            $reason === '',
            ValidationException::withMessages(['reason' => __('admin.reason_required')]),
        );

        DB::transaction(function () use ($actor, $folder, $reason): void {
            $folder->forceFill(['is_public' => false])->save();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::MakeFolderPrivate,
                'subject_type' => $folder::class,
                'subject_id' => $folder->getKey(),
                'reason' => $reason,
                'meta' => ['owner_id' => $folder->user_id, 'name' => $folder->name, 'public_id' => $folder->public_id],
            ]);
        });

        return $folder;
    }
}
