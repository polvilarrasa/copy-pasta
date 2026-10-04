<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Enums\Role;
use App\Mail\UserInvitationMail;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateUserByAdmin
{
    /**
     * Creates a member who has no password yet and sends them an invitation. The address stays unverified until the
     * member follows the invitation and chooses a password, which proves they own it. Nobody else learns the password.
     */
    public function handle(User $actor, string $username, string $email, Role $role): User
    {
        Gate::forUser($actor)->authorize('create', User::class);

        $user = DB::transaction(function () use ($actor, $username, $email, $role): User {
            $user = new User;
            $user->forceFill([
                'username' => $username,
                'email' => $email,
                'role' => $role,
                // An unguessable placeholder that nobody holds, so the account cannot be entered before the invitation.
                'password' => Str::random(64),
                'email_verified_at' => null,
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

        Mail::to($user)->queue(new UserInvitationMail($user));

        return $user;
    }
}
