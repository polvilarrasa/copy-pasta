<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class ResendEmailVerification
{
    public const MAX_RESENDS_PER_HOUR = 3;

    /**
     * Sends the verification email again to a member whose address is still unverified. Returns false when there is
     * nothing to send.
     *
     * @throws ThrottleRequestsException
     */
    public function handle(User $actor, User $target): bool
    {
        Gate::forUser($actor)->authorize('resendVerification', $target);

        if ($target->hasVerifiedEmail()) {
            return false;
        }

        throw_unless(
            RateLimiter::attempt('verification-resend:'.$target->getKey(), self::MAX_RESENDS_PER_HOUR, fn (): bool => true, 3600),
            new ThrottleRequestsException(__('admin.users.actions.verification_rate_limited')),
        );

        DB::transaction(function () use ($actor, $target): void {
            $target->sendEmailVerificationNotification();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::VerificationResent,
                'subject_type' => $target::class,
                'subject_id' => $target->getKey(),
            ]);
        });

        return true;
    }
}
