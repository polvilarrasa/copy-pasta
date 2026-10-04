<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ResolveCopypastaTags
{
    public const MAX_TAGS = 5;

    /**
     * Only active tags may be chosen, between one and five per copy-pasta.
     *
     * @param  array<int, int|string>  $tagIds
     * @return array<int, int>
     */
    public function handle(array $tagIds): array
    {
        /** @var Collection<int, int> $ids */
        $ids = collect($tagIds)->map(fn (int|string $id): int => (int) $id)->unique()->values();

        if ($ids->isEmpty() || $ids->count() > self::MAX_TAGS) {
            throw ValidationException::withMessages([
                'tag_ids' => __('app.errors.tags_count', ['max' => self::MAX_TAGS]),
            ]);
        }

        $activeCount = Tag::query()->whereKey($ids)->where('is_active', true)->count();

        if ($activeCount !== $ids->count()) {
            throw ValidationException::withMessages([
                'tag_ids' => __('app.errors.tags_inactive'),
            ]);
        }

        return $ids->all();
    }
}
