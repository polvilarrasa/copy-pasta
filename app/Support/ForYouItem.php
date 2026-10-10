<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Copypasta;
use App\Models\Tag;

/**
 * A copy-pasta of the "Para ti" feed with where it came from and why it is there. The explanation is `liked` (with
 * the member's favorite of its tags), `discover` or `recent`.
 */
final readonly class ForYouItem
{
    public function __construct(
        public Copypasta $copypasta,
        public string $group,
        public int $position,
        public string $explanation,
        public ?Tag $tag = null,
    ) {}
}
