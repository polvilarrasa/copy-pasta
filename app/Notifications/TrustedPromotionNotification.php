<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;

class TrustedPromotionNotification extends AppNotification
{
    public function type(): NotificationType
    {
        return NotificationType::TrustedPromotion;
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return [];
    }
}
