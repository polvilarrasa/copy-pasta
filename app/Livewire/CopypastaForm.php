<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\DeleteCopypasta;
use App\Actions\FindDuplicateCopypasta;
use App\Actions\PublishCopypasta;
use App\Actions\ResolveCopypastaTags;
use App\Actions\UpdateCopypasta;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use App\Support\AvatarColor;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Shared by /publicar and /c/{ulid}/editar: `copypasta` is null when creating, set when editing.
 */
class CopypastaForm extends Component
{
    public ?Copypasta $copypasta = null;

    public string $title = '';

    public string $body = '';

    /** @var array<int, int> */
    public array $tagIds = [];

    public bool $isNsfw = false;

    public function mount(?Copypasta $copypasta = null): void
    {
        if ($copypasta instanceof Copypasta) {
            abort_unless(Gate::allows('update', $copypasta), 404);

            $this->copypasta = $copypasta;
            $this->title = $copypasta->title;
            $this->body = $copypasta->body;
            $this->isNsfw = $copypasta->is_nsfw;
            $this->tagIds = $copypasta->tags()->where('is_active', true)->pluck('tags.id')->all();

            return;
        }

        Gate::authorize('create', Copypasta::class);
    }

    public function toggleTag(int $tagId): void
    {
        $this->tagIds = in_array($tagId, $this->tagIds, true)
            ? array_values(array_diff($this->tagIds, [$tagId]))
            : (count($this->tagIds) < ResolveCopypastaTags::MAX_TAGS ? [...$this->tagIds, $tagId] : $this->tagIds);
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:10000'],
        ]);

        $data = [
            'title' => $this->title,
            'body' => $this->body,
            'is_nsfw' => $this->isNsfw,
            'tag_ids' => $this->tagIds,
        ];

        $copypasta = $this->copypasta instanceof Copypasta
            ? app(UpdateCopypasta::class)->handle($this->user(), $this->copypasta, $data)
            : app(PublishCopypasta::class)->handle($this->user(), $data);

        $this->redirectRoute('copypastas.show', [$copypasta, $copypasta->slug], navigate: true);
    }

    public function delete(): void
    {
        if (! $this->copypasta instanceof Copypasta) {
            return;
        }

        app(DeleteCopypasta::class)->handle($this->user(), $this->copypasta);

        $this->redirectRoute('copypastas.mine', navigate: true);
    }

    /**
     * @return array<int, array{name: string, color: string}>
     */
    #[Computed]
    public function previewTags(): array
    {
        $tags = $this->availableTags();

        return collect($this->tagIds)
            ->map(fn (int $id): ?Tag => $tags->firstWhere('id', $id))
            ->filter()
            ->map(fn (Tag $tag): array => ['name' => $tag->name, 'color' => $tag->color])
            ->values()
            ->all();
    }

    #[Computed]
    public function duplicate(): ?Copypasta
    {
        if (trim($this->body) === '') {
            return null;
        }

        return app(FindDuplicateCopypasta::class)->handle($this->body, $this->copypasta);
    }

    public function render(): View
    {
        $user = $this->user();

        return view('livewire.copypasta-form', [
            'tags' => $this->availableTags(),
            'previewAuthor' => $user->displayName(),
            'previewAuthorHue' => AvatarColor::hueFor($user->getKey()),
        ]);
    }

    /**
     * @return EloquentCollection<int, Tag>
     */
    private function availableTags(): EloquentCollection
    {
        return Tag::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug', 'color']);
    }

    private function user(): User
    {
        $user = Auth::user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
