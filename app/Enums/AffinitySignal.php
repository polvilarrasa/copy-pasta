<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * An action that moves the member's affinity with the tags of a copy-pasta. The weights are in config/affinity.php.
 * Saving a copy-pasta is Favorites only: adding to any other folder is not a signal.
 */
enum AffinitySignal: string
{
    case Copy = 'copy';
    case Favorite = 'favorite';
    case Upvote = 'upvote';
    case Downvote = 'downvote';
    case Dismiss = 'dismiss';

    public function weight(): float
    {
        return (float) config('affinity.weights.'.$this->value);
    }

    /**
     * What a vote contributes: nothing without a vote. Changing or withdrawing a vote applies the difference, so the
     * old contribution is reverted.
     */
    public static function voteWeight(?int $vote): float
    {
        return match ($vote) {
            1 => self::Upvote->weight(),
            -1 => self::Downvote->weight(),
            default => 0.0,
        };
    }
}
