<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;

class SendPasswordReset
{
    /**
     * Sends the standard reset link to the member. The admin never sees or sets the password. Returns whether the
     * broker sent the link (it refuses when it is throttled).
     */
    public function handle(User $actor, User $target): bool
    {
        Gate::forUser($actor)->authorize('sendPasswordReset', $target);

        $status = Password::broker()->sendResetLink(['email' => $target->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return false;
        }

        DB::transaction(fn () => ModerationAction::query()->create([
            'actor_id' => $actor->getKey(),
            'action' => ModerationActionType::SendPasswordReset,
            'subject_type' => $target::class,
            'subject_id' => $target->getKey(),
        ]));

        return true;
    }
}
