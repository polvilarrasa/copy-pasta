<?php

declare(strict_types=1);

namespace App\Support;

/**
 * What the achievements section of a profile shows. Pending achievements and hidden secrets are only filled in for the
 * profile's owner; everyone else gets the earned ones and the number of secrets earned.
 */
final readonly class ProfileAchievements
{
    /**
     * @param  list<AchievementEntry>  $earned
     * @param  list<AchievementEntry>  $pending
     */
    public function __construct(
        public array $earned,
        public array $pending,
        public int $hiddenSecrets,
        public int $secretsEarned,
        public bool $isOwner,
    ) {}
}
