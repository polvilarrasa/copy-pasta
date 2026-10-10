<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\ListActiveTags;
use App\Actions\SkipOnboarding;
use App\Actions\UpdateFavoriteTags;
use App\Models\User;
use App\Support\TagAffinities;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * The welcome screen and, in editing mode, the screen to change the favorite tags: the same picker with different
 * copy. Saving needs at least three tags.
 */
class Welcome extends Component
{
    public bool $editing = false;

    /** @var array<int, int> */
    public array $selected = [];

    public function mount(bool $editing = false): void
    {
        $this->editing = $editing;
        $this->selected = app(TagAffinities::class)->favoriteTagIds($this->user());
    }

    public function toggle(int $tagId): void
    {
        $this->selected = in_array($tagId, $this->selected, true)
            ? array_values(array_diff($this->selected, [$tagId]))
            : [...$this->selected, $tagId];

        $this->resetErrorBag();
    }

    public function save(): void
    {
        app(UpdateFavoriteTags::class)->handle($this->user(), $this->selected);

        $this->redirectRoute('home', navigate: true);
    }

    public function skip(): void
    {
        app(SkipOnboarding::class)->handle($this->user());

        $this->redirectRoute('home', navigate: true);
    }

    public function render(): View
    {
        $minimum = (int) config('affinity.onboarding_min_tags');

        return view('livewire.welcome', [
            'tags' => app(ListActiveTags::class)->handle(),
            'minimum' => $minimum,
            'missing' => max(0, $minimum - count($this->selected)),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
