<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\FeedSort;
use App\Models\Copypasta;
use Illuminate\Database\Eloquent\Builder;

final class FeedQuery
{
    /** @var Builder<Copypasta> */
    private Builder $query;

    public function __construct()
    {
        $this->query = Copypasta::query()->visible()->notOnlyInactiveTags();
    }

    public static function make(): self
    {
        return new self;
    }

    public function sort(FeedSort $sort, ?string $randomSeed = null): self
    {
        $this->query->sort($sort, $randomSeed);

        return $this;
    }

    /**
     * @param  array<int, string>  $tagSlugs
     */
    public function tags(array $tagSlugs): self
    {
        $this->query->withAllTags($tagSlugs);

        return $this;
    }

    public function search(?string $term): self
    {
        if (filled($term)) {
            $this->query->search($term);
        }

        return $this;
    }

    public function nsfw(bool $include): self
    {
        $this->query->nsfw($include);

        return $this;
    }

    /**
     * @return Builder<Copypasta>
     */
    public function builder(): Builder
    {
        return $this->query;
    }
}
