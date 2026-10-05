<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\ProfileValidationRules;
use App\Enums\EventType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ChangeUsername
{
    use ProfileValidationRules;

    public const COOLDOWN_DAYS = 30;

    /**
     * A member can change the username once every 30 days. The old name goes to the history, which is what keeps
     * its profile links working for a while. Changing to the same name is a no-op and does not use up the cooldown.
     */
    public function handle(User $user, string $username): User
    {
        if ($username === $user->username) {
            return $user;
        }

        if ($user->username_changed_at?->gt(now()->subDays(self::COOLDOWN_DAYS)) === true) {
            throw ValidationException::withMessages([
                'username' => __('auth.username_cooldown', ['days' => self::COOLDOWN_DAYS]),
            ]);
        }

        Validator::make(['username' => $username], ['username' => $this->usernameRules($user->getKey())])->validate();

        DB::transaction(function () use ($user, $username): void {
            $user->forceFill([
                'username' => $username,
                'username_changed_at' => now(),
            ])->save();
        });

        app(RecordEvent::class)->handle(EventType::UsernameChange, $user);

        return $user;
    }
}
