<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * A notification ready to render: the text is already written from lang/es and the destination resolved.
 */
final readonly class NotificationItem
{
    public function __construct(
        public string $id,
        public string $text,
        public ?string $url,
        public string $icon,
        public string $tone,
        public bool $isRead,
        public CarbonInterface $at,
    ) {}
}
