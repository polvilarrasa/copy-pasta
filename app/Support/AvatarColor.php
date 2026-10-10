<?php

declare(strict_types=1);

namespace App\Support;

class AvatarColor
{
    /**
     * The hue is derived from a stable key (the user id), so the same member always gets the same colour.
     */
    public static function hueFor(int|string $key): int
    {
        return crc32((string) $key) % 360;
    }

    /**
     * Two-tone gradient with a light highlight, the generated avatar of the design (docs/design/source/Card.dc.html).
     */
    public static function gradient(int $hue): string
    {
        $secondHue = ($hue + 60) % 360;

        return sprintf(
            'radial-gradient(circle at 70%% 28%%, rgba(255, 255, 255, 0.85) 0 14%%, transparent 15%%), linear-gradient(140deg, hsl(%d, 85%%, 62%%), hsl(%d, 80%%, 56%%))',
            $hue,
            $secondHue,
        );
    }
}
