<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Achievement;
use Carbon\CarbonInterface;

/**
 * One row of the achievements section of a profile: an earned achievement (with its date and rarity) or a pending one
 * (with its progress).
 */
final readonly class AchievementEntry
{
    public function __construct(
        public Achievement $achievement,
        public ?CarbonInterface $unlockedAt = null,
        public ?string $rarity = null,
        public bool $isActiveTitle = false,
        public int $current = 0,
        public int $max = 0,
    ) {}

    public function percent(): int
    {
        return $this->max === 0 ? 0 : (int) min(100, floor($this->current / $this->max * 100));
    }
}
