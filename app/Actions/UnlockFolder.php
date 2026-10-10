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

class UnlockFolder
{
    /**
     * Staff lift the lock of a folder they made private, with a mandatory reason. The folder stays private: unlocking
     * only lets its owner decide again whether to make it public. The change is logged.
     */
    public function handle(User $actor, Folder $folder, string $reason): Folder
    {
        Gate::forUser($actor)->authorize('unlock', $folder);

        $reason = trim($reason);

        throw_if(
            $reason === '',
            ValidationException::withMessages(['reason' => __('admin.reason_required')]),
        );

        DB::transaction(function () use ($actor, $folder, $reason): void {
            $lockReason = $folder->public_lock_reason;

            $folder->forceFill(['public_locked_at' => null, 'public_lock_reason' => null])->save();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::UnlockFolder,
                'subject_type' => $folder::class,
                'subject_id' => $folder->getKey(),
                'reason' => $reason,
                'meta' => ['owner_id' => $folder->user_id, 'name' => $folder->name, 'lock_reason' => $lockReason],
            ]);
        });

        return $folder;
    }
}
