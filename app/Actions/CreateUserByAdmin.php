<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Enums\Role;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CreateUserByAdmin
{
    public const TEMPORARY_PASSWORD_LENGTH = 16;

    /**
     * Creates a member with a generated temporary password that must be changed at the first sign-in. The address
     * is marked verified because the admin vouches for it. The password is returned once so the admin can share it.
     *
     * @return array{user: User, temporary_password: string}
     */
    public function handle(User $actor, string $username, string $email, Role $role): array
    {
        Gate::forUser($actor)->authorize('create', User::class);

        $temporaryPassword = Str::password(self::TEMPORARY_PASSWORD_LENGTH, symbols: false);

        $user = DB::transaction(function () use ($actor, $username, $email, $role, $temporaryPassword): User {
            $user = new User;
            $user->forceFill([
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'email_verified_at' => now(),
            ])->save();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::UserCreated,
                'subject_type' => $user::class,
                'subject_id' => $user->getKey(),
                'meta' => ['role' => $role->value],
            ]);

            return $user;
        });

        return ['user' => $user, 'temporary_password' => $temporaryPassword];
    }
}
