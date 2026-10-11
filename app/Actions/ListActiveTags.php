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

    /**
     * How many tags a member has to pick on the welcome screen: the configured minimum, or every active tag when there
     * are fewer than that.
     */
    public function onboardingMinimum(): int
    {
        return min((int) config('affinity.onboarding_min_tags'), $this->handle()->count());
    }

    /**
     * The welcome screen is only offered when there are as many active tags as the configured minimum.
     */
    public function canOfferOnboarding(): bool
    {
        return $this->handle()->count() >= (int) config('affinity.onboarding_min_tags');
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
