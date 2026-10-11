<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Copypasta;

class CopypastaVariantNotification extends AppNotification
{
    public function __construct(public Copypasta $parent) {}

    public function type(): NotificationType
    {
        return NotificationType::VariantPublished;
    }

    /**
     * `copypasta_id` is the original that was remixed. A notification can grow into a group: `count` is how many
     * variants were published while it was unread. It names nobody.
     *
     * @return array{copypasta_id: string, count: int}
     */
    protected function payload(): array
    {
        return ['copypasta_id' => $this->parent->getKey(), 'count' => 1];
    }
}
