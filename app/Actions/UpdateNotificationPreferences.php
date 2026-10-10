<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationType;
use App\Models\User;

class UpdateNotificationPreferences
{
    /**
     * Stores which configurable types the member wants. Unknown keys and mandatory types are ignored; a type missing
     * from the input keeps its current value.
     *
     * @param  array<string, mixed>  $preferences
     */
    public function handle(User $user, array $preferences): User
    {
        $stored = $user->notification_prefs ?? [];

        foreach (NotificationType::configurable() as $type) {
            if (array_key_exists($type->value, $preferences)) {
                $stored[$type->value] = (bool) $preferences[$type->value];
            }
        }

        $user->forceFill(['notification_prefs' => $stored])->save();

        return $user;
    }
}
