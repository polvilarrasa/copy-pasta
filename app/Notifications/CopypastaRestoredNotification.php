<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Copypasta;

class CopypastaRestoredNotification extends AppNotification
{
    public function __construct(public Copypasta $copypasta) {}

    public function type(): NotificationType
    {
        return NotificationType::CopypastaRestored;
    }

    /**
     * @return array{copypasta_id: string}
     */
    protected function payload(): array
    {
        return ['copypasta_id' => $this->copypasta->getKey()];
    }
}
