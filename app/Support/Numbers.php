<?php

declare(strict_types=1);

namespace App\Support;

class Numbers
{
    /**
     * A count above one thousand shows as one decimal of thousands with a comma ("1,8k"); below that, the plain
     * number with a dot as the thousands separator, the Spanish convention used across the design.
     */
    public static function abbreviate(int $value): string
    {
        return $value >= 1000
            ? number_format($value / 1000, 1, ',', '.').'k'
            : number_format($value, 0, ',', '.');
    }
}
