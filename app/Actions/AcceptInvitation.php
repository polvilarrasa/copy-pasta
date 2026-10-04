<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

class AcceptInvitation
{
    /**
     * Sets the member's own password. Following the invitation proves the address belongs to them, so it is verified.
     */
    public function handle(User $user, string $password): void
    {
        $user->forceFill([
            'password' => $password,
            'email_verified_at' => now(),
        ])->save();
    }
}
