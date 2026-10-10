<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Achievement;
use App\Enums\NotificationType;

class AchievementUnlockedNotification extends AppNotification
{
    public function __construct(public Achievement $achievement) {}

    public function type(): NotificationType
    {
        return NotificationType::AchievementUnlocked;
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return ['achievement' => $this->achievement->value];
    }
}
