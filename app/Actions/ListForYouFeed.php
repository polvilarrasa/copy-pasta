<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use App\Support\ForYouItem;
use App\Support\ForYouPage;
use App\Support\TagAffinities;
use Illuminate\Database\Eloquent\Collection;

class ListForYouFeed
{
    /** Candidates read past the page, to make up for the ones that stopped being visible since they were listed. */
    private const SPARE = 10;

    public function __construct(private BuildForYouCandidates $candidates, private TagAffinities $affinities) {}

    /**
     * The first `$limit` copy-pastas of the member's "Para ti" list, in list order. What was hidden, deleted or
     * dismissed since the list was built is left out. A list that has nothing to show is rebuilt once, so the tab is
     * never empty while there is content.
     */
    public function handle(User $user, int $limit, bool $refresh = false): ForYouPage
    {
        $candidates = $this->candidates->candidates($user, $refresh);
        $slice = array_slice($candidates, 0, $limit + self::SPARE);

        $copypastas = $this->load($user, array_column($slice, 'id'));

        $available = [];

        foreach ($slice as $index => $candidate) {
            if ($copypastas->has($candidate['id'])) {
                $available[$index] = $candidate;
            }
        }

        if ($available === [] && ! $refresh) {
            return $this->handle($user, $limit, refresh: true);
        }

        $hasMore = count($available) > $limit || count($candidates) > count($slice);
        $effective = $this->affinities->effective($user);

        $items = [];

        foreach (array_slice($available, 0, $limit, preserve_keys: true) as $index => $candidate) {
            /** @var Copypasta $copypasta */
            $copypasta = $copypastas->get($candidate['id']);

            [$explanation, $tag] = $this->explain($candidate['group'], $copypasta, $effective);

            $items[] = new ForYouItem($copypasta, $candidate['group'], $index, $explanation, $tag);
        }

        return new ForYouPage($items, $hasMore);
    }

    /**
     * @param  list<string>  $ids
     * @return Collection<string, Copypasta>
     */
    private function load(User $user, array $ids): Collection
    {
        return Copypasta::query()
            ->visible()
            ->whereIn('copypastas.id', $ids)
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('copypasta_dismissals')
                ->whereColumn('copypasta_dismissals.copypasta_id', 'copypastas.id')->where('copypasta_dismissals.user_id', $user->getKey()))
            ->withViewerState($user)
            ->with(['user:id,username,title_key,anonymized_at,banned_at', 'tags:id,name,slug,color'])
            ->get()
            ->keyBy('id');
    }

    /**
     * "Porque te gusta #etiqueta" with the tag of the copy-pasta the member likes most, "Recién publicado" for the
     * recent group, and "Para que descubras algo nuevo" otherwise.
     *
     * @param  array<int, float>  $effective
     * @return array{0: string, 1: Tag|null}
     */
    private function explain(string $group, Copypasta $copypasta, array $effective): array
    {
        if ($group === 'recent') {
            return ['recent', null];
        }

        if ($group === 'explore') {
            return ['discover', null];
        }

        $best = $copypasta->tags
            ->filter(fn (Tag $tag): bool => ($effective[$tag->id] ?? 0.0) > 0)
            ->sortByDesc(fn (Tag $tag): float => $effective[$tag->id])
            ->first();

        return $best === null ? ['discover', null] : ['liked', $best];
    }
}
