<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\ListActiveTags;
use App\Enums\FeedSort;
use App\Http\Controllers\Public\NsfwConfirmationController;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use App\Queries\FeedQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class Feed extends Component
{
    public const PER_PAGE = 20;

    /** Largest key the random feed can hold: `random_key` is an integer in [0, MAX_SEED]. */
    private const MAX_SEED = 2147483646;

    private const FILTER_PROPERTIES = ['sort', 'tags', 'search', 'nsfw'];

    /** Set on alias pages (/top/semana, /nuevos…) where the order is not user-selectable. */
    public ?string $fixedSort = null;

    /** Set on /etiqueta/{slug}; combined with the tags in the query string. */
    public ?string $tagSlug = null;

    #[Url(as: 'sort')]
    public string $sort = FeedSort::Random->value;

    /** Comma-separated tag slugs, e.g. `?tags=a,b`. */
    #[Url(as: 'tags')]
    public string $tags = '';

    #[Url(as: 'q')]
    public string $search = '';

    /** Only meaningful for anonymous visitors who have confirmed they are of age. */
    #[Url(as: 'nsfw')]
    public bool $nsfw = false;

    /** Where the random feed starts walking over `random_key`; in the URL so pages stay stable on reload. */
    #[Url(as: 'seed')]
    public ?int $seed = null;

    public int $limit = self::PER_PAGE;

    public function mount(): void
    {
        $this->ensureSeed();
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::FILTER_PROPERTIES, true)) {
            $this->limit = self::PER_PAGE;
        }

        if ($property === 'sort') {
            $this->ensureSeed();
        }
    }

    public function loadMore(): void
    {
        $this->limit += self::PER_PAGE;
    }

    public function shuffle(): void
    {
        $this->seed = self::newSeed();

        $this->limit = self::PER_PAGE;
    }

    public function toggleTag(string $slug): void
    {
        $selected = $this->requestedTagSlugs();

        $this->tags = ($selected->contains($slug) ? $selected->reject($slug) : $selected->push($slug))
            ->implode(',');
    }

    public function render(): View
    {
        $results = $this->feedPage($this->limit + 1);

        return view('livewire.feed', [
            'copypastas' => $results->take($this->limit),
            'hasMore' => $results->count() > $this->limit,
            'sortOptions' => $this->fixedSort === null ? FeedSort::cases() : [],
            'activeSort' => $this->activeSort(),
            'availableTags' => app(ListActiveTags::class)->handle(),
            'activeTagSlugs' => $this->activeTagSlugs(),
            'includesNsfw' => $this->includesNsfw(),
            'canToggleNsfw' => ! auth()->check(),
            'hasNsfwConsent' => $this->hasNsfwConsent(),
            'nsfwConfirmRoute' => route('nsfw.confirm'),
        ]);
    }

    /**
     * @return Builder<Copypasta>
     */
    private function feedQuery(): Builder
    {
        return FeedQuery::make()
            ->sort($this->activeSort())
            ->tags($this->activeTagSlugs())
            ->search($this->search)
            ->nsfw($this->includesNsfw())
            ->builder()
            ->withViewerState($this->viewer());
    }

    private function viewer(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    private function activeSort(): FeedSort
    {
        return FeedSort::tryFrom($this->fixedSort ?? $this->sort) ?? FeedSort::Random;
    }

    /**
     * Unknown or deactivated tags are dropped, so stale links keep working.
     *
     * @return array<int, string>
     */
    private function activeTagSlugs(): array
    {
        $requested = $this->requestedTagSlugs()
            ->when($this->tagSlug !== null, fn (Collection $slugs) => $slugs->push($this->tagSlug))
            ->unique()
            ->values();

        if ($requested->isEmpty()) {
            return [];
        }

        return Tag::query()
            ->where('is_active', true)
            ->whereIn('slug', $requested)
            ->pluck('slug')
            ->all();
    }

    /**
     * @return Collection<int, non-falsy-string>
     */
    private function requestedTagSlugs(): Collection
    {
        return collect(explode(',', $this->tags))
            ->map(fn (string $slug): string => trim($slug))
            ->filter()
            ->values();
    }

    private function includesNsfw(): bool
    {
        $user = auth()->user();

        if ($user instanceof User) {
            return $user->canSeeNsfw();
        }

        return $this->nsfw && $this->hasNsfwConsent();
    }

    private function hasNsfwConsent(): bool
    {
        return request()->cookie(NsfwConfirmationController::COOKIE) === '1';
    }

    /**
     * The random order continues from the seed to the end of the key space and then wraps around to the start, so
     * every copy-pasta appears once per seed and pages never overlap.
     *
     * @return EloquentCollection<int, Copypasta>
     */
    private function feedPage(int $size): EloquentCollection
    {
        $page = $this->activeSort() === FeedSort::Random
            ? $this->randomPage($size)
            : $this->feedQuery()->limit($size)->get();

        return $page->load(['user:id,username,anonymized_at', 'tags:id,name,slug,color']);
    }

    /**
     * @return EloquentCollection<int, Copypasta>
     */
    private function randomPage(int $size): EloquentCollection
    {
        $seed = $this->seed ?? 0;
        $page = $this->feedQuery()->randomKeyFrom($seed)->limit($size)->get();

        if ($page->count() < $size) {
            $page = $page->merge(
                $this->feedQuery()->randomKeyBefore($seed)->limit($size - $page->count())->get(),
            );
        }

        return $page;
    }

    private function ensureSeed(): void
    {
        if ($this->seed === null && $this->activeSort() === FeedSort::Random) {
            $this->seed = self::newSeed();
        }
    }

    private static function newSeed(): int
    {
        return random_int(0, self::MAX_SEED);
    }
}
