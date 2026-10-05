<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Invisible characters that let a title read differently from what it says: bidi overrides and isolates, and zero
 * width characters. They are removed from titles on publish, edit and in the data migration that cleans old titles.
 */
final class UnicodeText
{
    private const INVISIBLE_PATTERN = '/[\x{202A}-\x{202E}\x{2066}-\x{2069}\x{200B}-\x{200D}\x{FEFF}]/u';

    public static function cleanTitle(string $title): string
    {
        return trim(preg_replace(self::INVISIBLE_PATTERN, '', $title) ?? '');
    }
}
