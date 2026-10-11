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

    /**
     * The same list without U+200D (zero width joiner): in the image text a joiner is what holds a family or a
     * profession emoji together, and it cannot reorder or hide anything there.
     */
    private const INVISIBLE_PATTERN_KEEPING_JOINER = '/[\x{202A}-\x{202E}\x{2066}-\x{2069}\x{200B}\x{200C}\x{FEFF}]/u';

    public static function cleanTitle(string $title): string
    {
        return trim(preg_replace(self::INVISIBLE_PATTERN, '', $title) ?? '');
    }

    /**
     * Neutralizes the direction controls and zero width characters of a text that is about to be drawn into an image.
     */
    public static function forRendering(string $text): string
    {
        return preg_replace(self::INVISIBLE_PATTERN_KEEPING_JOINER, '', $text) ?? '';
    }
}
