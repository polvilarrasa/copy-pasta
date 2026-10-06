<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Enums\Theme;
use App\Models\User;

class UpdateThemePreference
{
    public function __construct(private RecordEvent $recordEvent) {}

    /**
     * Stores the theme on the account. Anonymous visitors keep it in a cookie, which the controller sets.
     */
    public function handle(Theme $theme, ?User $user = null): void
    {
        $user?->forceFill(['theme' => $theme])->save();

        $this->recordEvent->handle(EventType::ThemeChange, $user, context: ['theme' => $theme->value]);
    }
}
