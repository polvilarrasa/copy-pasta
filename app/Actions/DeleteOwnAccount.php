<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteOwnAccount
{
    public function __construct(private AnonymizeUser $anonymizeUser) {}

    /**
     * Lets a member delete their own account. The account is anonymized rather than removed, so the copy-pastas
     * other people keep in their folders survive.
     */
    public function handle(User $user): void
    {
        Gate::forUser($user)->authorize('deleteOwnAccount', $user);

        $this->anonymizeUser->handle($user, $user);
    }
}
