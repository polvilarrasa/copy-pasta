<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

class ChangeTemporaryPassword
{
    /**
     * Replaces the temporary password the member was given and lifts the requirement to change it. The caller
     * checks the current password and the new rules.
     */
    public function handle(User $user, string $newPassword): User
    {
        $user->forceFill([
            'password' => $newPassword,
            'must_change_password' => false,
        ])->save();

        return $user;
    }
}
