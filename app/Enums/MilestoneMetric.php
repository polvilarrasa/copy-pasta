<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Copypasta;

enum MilestoneMetric: string
{
    case Copies = 'copies';
    case Upvotes = 'upvotes';

    /** @var list<int> */
    public const THRESHOLDS = [10, 100, 1000];

    public function count(Copypasta $copypasta): int
    {
        return match ($this) {
            self::Copies => $copypasta->copies_count,
            self::Upvotes => $copypasta->upvotes_count,
        };
    }
}
