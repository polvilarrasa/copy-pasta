<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Models\User;

class SkipOnboarding
{
    public function __construct(private RecordEvent $recordEvent) {}

    /**
     * "Saltar por ahora": the welcome screen is done and no tag is saved. Skipping again changes nothing.
     */
    public function handle(User $user): User
    {
        if ($user->onboarded_at === null) {
            $user->forceFill(['onboarded_at' => now()])->save();
        }

        $this->recordEvent->handle(EventType::FavoriteTagsUpdate, $user, context: ['skipped' => true]);

        return $user;
    }
}
