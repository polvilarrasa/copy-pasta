<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\FeedSort;
use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Component;

class ProfileCopypastas extends Component
{
    public const PER_PAGE = 20;

    public User $user;

    public string $tab = 'top';

    public int $limit = self::PER_PAGE;

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function selectTab(string $tab): void
    {
        if (! in_array($tab, ['top', 'new'], true)) {
            return;
        }

        $this->tab = $tab;
        $this->limit = self::PER_PAGE;
    }

    public function loadMore(): void
    {
        $this->limit += self::PER_PAGE;
    }

    public function render(): View
    {
        $results = $this->copypastas($this->limit + 1);

        return view('livewire.profile-copypastas', [
            'copypastas' => $results->take($this->limit),
            'hasMore' => $results->count() > $this->limit,
        ]);
    }

    /**
     * @return EloquentCollection<int, Copypasta>
     */
    private function copypastas(int $size): EloquentCollection
    {
        return Copypasta::query()
            ->where('user_id', $this->user->getKey())
            ->visible()
            ->sort($this->tab === 'top' ? FeedSort::TopAll : FeedSort::Newest)
            ->withViewerState($this->viewer())
            ->with(['user:id,username,anonymized_at,banned_at', 'tags:id,name,slug,color'])
            ->limit($size)
            ->get();
    }

    private function viewer(): ?User
    {
        $viewer = auth()->user();

        return $viewer instanceof User ? $viewer : null;
    }
}
