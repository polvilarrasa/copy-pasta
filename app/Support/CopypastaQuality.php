<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * The quality and freshness formula, in one place: (score + 2 × copies) / (age in hours + 2)^1.5. The "Para ti" groups
 * and the copy-pasta of the day both order by it.
 */
final class CopypastaQuality
{
    public const COPIES_WEIGHT = 2;

    public const AGE_OFFSET_HOURS = 2;

    public const AGE_EXPONENT = 1.5;

    /**
     * Orders the query from the best to the worst, then newest, then by id so the order is total.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function orderBest(Builder $query): Builder
    {
        return $query
            ->orderByRaw(
                '(copypastas.score + ? * copypastas.copies_count)::float / power(GREATEST(0, EXTRACT(EPOCH FROM (?::timestamp - copypastas.published_at)) / 3600) + ?, ?) DESC',
                [self::COPIES_WEIGHT, now()->toDateTimeString(), self::AGE_OFFSET_HOURS, self::AGE_EXPONENT],
            )
            ->orderByDesc('copypastas.published_at')
            ->orderByDesc('copypastas.id');
    }
}
