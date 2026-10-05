<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Word-level diff between two texts, for the moderation queue. Words are compared as they are, so whitespace
 * differences show as equal and are not repeated.
 */
final class TextDiff
{
    /**
     * @return list<array{type: 'equal'|'delete'|'insert', text: string}>
     */
    public static function words(string $from, string $to): array
    {
        $old = self::words_of($from);
        $new = self::words_of($to);

        $lengths = self::commonSubsequenceLengths($old, $new);

        $segments = [];
        $i = 0;
        $j = 0;

        while ($i < count($old) && $j < count($new)) {
            if ($old[$i] === $new[$j]) {
                $segments[] = ['type' => 'equal', 'text' => $old[$i]];
                $i++;
                $j++;
            } elseif ($lengths[$i + 1][$j] >= $lengths[$i][$j + 1]) {
                $segments[] = ['type' => 'delete', 'text' => $old[$i]];
                $i++;
            } else {
                $segments[] = ['type' => 'insert', 'text' => $new[$j]];
                $j++;
            }
        }

        foreach (array_slice($old, $i) as $word) {
            $segments[] = ['type' => 'delete', 'text' => $word];
        }

        foreach (array_slice($new, $j) as $word) {
            $segments[] = ['type' => 'insert', 'text' => $word];
        }

        return self::merged($segments);
    }

    /**
     * @return list<string>
     */
    private static function words_of(string $text): array
    {
        return preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return list<list<int>>
     */
    private static function commonSubsequenceLengths(array $old, array $new): array
    {
        $lengths = array_fill(0, count($old) + 1, array_fill(0, count($new) + 1, 0));

        for ($i = count($old) - 1; $i >= 0; $i--) {
            for ($j = count($new) - 1; $j >= 0; $j--) {
                $lengths[$i][$j] = $old[$i] === $new[$j]
                    ? $lengths[$i + 1][$j + 1] + 1
                    : max($lengths[$i + 1][$j], $lengths[$i][$j + 1]);
            }
        }

        return $lengths;
    }

    /**
     * Joins consecutive words of the same type, so the view renders one element per run.
     *
     * @param  list<array{type: 'equal'|'delete'|'insert', text: string}>  $segments
     * @return list<array{type: 'equal'|'delete'|'insert', text: string}>
     */
    private static function merged(array $segments): array
    {
        $merged = [];

        foreach ($segments as $segment) {
            $last = array_key_last($merged);

            if ($last !== null && $merged[$last]['type'] === $segment['type']) {
                $merged[$last]['text'] .= ' '.$segment['text'];

                continue;
            }

            $merged[] = $segment;
        }

        return $merged;
    }
}
