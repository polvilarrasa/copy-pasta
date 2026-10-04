<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ImpersonateUser
{
    public const STARTED_AT_KEY = 'impersonation.started_at';

    /**
     * Starts acting as the member. The start is logged here; the matching end is logged by StopImpersonating.
     */
    public function handle(User $actor, User $target): bool
    {
        Gate::forUser($actor)->authorize('impersonate', $target);

        throw_if(is_impersonating(), new AuthorizationException(__('moderation.impersonation.already_impersonating')));

        return DB::transaction(function () use ($actor, $target): bool {
            if (! $actor->impersonate($target)) {
                return false;
            }

            // A confirmation given by the admin must not unlock the member's security settings.
            session()->put(self::STARTED_AT_KEY, now()->timestamp);
            session()->forget('auth.password_confirmed_at');

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::ImpersonateStart,
                'subject_type' => $target::class,
                'subject_id' => $target->getKey(),
            ]);

            return true;
        });
    }
}
