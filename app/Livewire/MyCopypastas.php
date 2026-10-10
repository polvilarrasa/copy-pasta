<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class MyCopypastas extends Component
{
    public const PER_PAGE = 20;

    public int $limit = self::PER_PAGE;

    public ?string $confirmingDeleteId = null;

    public function mount(): void
    {
        Gate::authorize('viewOwn', Copypasta::class);
    }

    public function loadMore(): void
    {
        $this->limit += self::PER_PAGE;
    }

    public function confirmDelete(string $copypastaId): void
    {
        $this->confirmingDeleteId = $copypastaId;
    }

    public function resetConfirmingDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        if ($this->confirmingDeleteId === null) {
            return;
        }

        $copypasta = Copypasta::query()->whereKey($this->confirmingDeleteId)->firstOrFail();

        Gate::authorize('delete', $copypasta);

        $copypasta->delete();

        $this->confirmingDeleteId = null;
    }

    public function render(): View
    {
        $results = $this->copypastas($this->limit + 1);

        return view('livewire.my-copypastas', [
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
            ->where('user_id', $this->user()->getKey())
            ->orderByDesc('published_at')
            ->limit($size)
            ->get();
    }

    private function user(): User
    {
        $user = Auth::user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
