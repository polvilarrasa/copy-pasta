<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\MilestoneMetric;
use App\Enums\NotificationType;
use App\Models\Copypasta;

class CopypastaMilestoneNotification extends AppNotification
{
    public function __construct(
        public Copypasta $copypasta,
        public MilestoneMetric $metric,
        public int $threshold,
    ) {}

    public function type(): NotificationType
    {
        return NotificationType::Milestone;
    }

    /**
     * A milestone notification can grow into a group: `milestones` lists every milestone reached while it was unread.
     *
     * @return array{copypasta_id: string, milestones: list<array{metric: string, threshold: int}>}
     */
    protected function payload(): array
    {
        return [
            'copypasta_id' => $this->copypasta->getKey(),
            'milestones' => [['metric' => $this->metric->value, 'threshold' => $this->threshold]],
        ];
    }
}
