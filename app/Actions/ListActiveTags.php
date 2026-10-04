<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ListActiveTags
{
    /**
     * Versioned key: entries written by the first version held serialized models, which do not survive a cache
     * round trip, so they must never be read back.
     */
    public const CACHE_KEY = 'tags.active.rows';

    /**
     * The active tags for the feed filters, ordered by name. The cache holds plain attributes, which are hydrated
     * back into models on every read. Cleared whenever a tag is saved.
     *
     * @return Collection<int, Tag>
     */
    public function handle(): Collection
    {
        $rows = Cache::rememberForever(self::CACHE_KEY, fn (): array => Tag::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->toArray());

        return Tag::hydrate($rows);
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
