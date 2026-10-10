<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Context that the page sends along with an action: the list the copy-pasta was shown in, its position there, and the
 * reference code of a shared link. Every value is checked here, so an event never stores what a client invents.
 */
final class EventContext
{
    private const MAX_POSITION = 1000;

    /**
     * @return array{source?: string, position?: int, group?: string, ref?: string}
     */
    public static function fromRequest(Request $request): array
    {
        return array_filter([
            'source' => self::source($request->input('source', $request->query('from'))),
            'position' => self::position($request->input('position', $request->query('pos'))),
            'group' => self::group($request->input('group')),
            'ref' => self::ref($request->input('ref', $request->query('ref'))),
        ], fn (mixed $value): bool => $value !== null);
    }

    private static function source(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[a-z_]{1,32}$/', $value) === 1 ? $value : null;
    }

    private static function position(mixed $value): ?int
    {
        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            return null;
        }

        $position = (int) $value;

        return $position >= 0 && $position <= self::MAX_POSITION ? $position : null;
    }

    /**
     * The "Para ti" group the copy-pasta came from, only meaningful together with that source.
     */
    private static function group(mixed $value): ?string
    {
        return in_array($value, ['affinity', 'explore', 'recent', 'fallback'], true) ? $value : null;
    }

    private static function ref(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9]{6,16}$/', $value) === 1 ? $value : null;
    }
}
