<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\Public\NsfwConfirmationController;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Component;

class PublicFolderCopypastas extends Component
{
    public const PER_PAGE = 20;

    public Folder $folder;

    public int $limit = self::PER_PAGE;

    public function mount(Folder $folder): void
    {
        abort_unless($folder->is_public, 404);

        $this->folder = $folder;
    }

    public function loadMore(): void
    {
        abort_unless($this->folder->refresh()->is_public, 404);

        $this->limit += self::PER_PAGE;
    }

    public function render(): View
    {
        $results = $this->copypastas($this->limit + 1);

        return view('livewire.public-folder-copypastas', [
            'copypastas' => $results->take($this->limit),
            'hasMore' => $results->count() > $this->limit,
        ]);
    }

    /**
     * What anyone can see: published copy-pastas that are neither hidden nor deleted, with no placeholder for the ones
     * that are not. Adult content follows the viewer's own preference, as everywhere else.
     *
     * @return EloquentCollection<int, Copypasta>
     */
    private function copypastas(int $size): EloquentCollection
    {
        return $this->folder->copypastas()
            ->visible()
            ->nsfw($this->includesNsfw())
            ->withViewerState($this->viewer())
            ->with(['user:id,username,title_key,anonymized_at,banned_at', 'tags:id,name,slug,color'])
            ->orderByDesc('copypasta_folder.created_at')
            ->limit($size)
            ->get();
    }

    private function includesNsfw(): bool
    {
        $viewer = $this->viewer();

        if ($viewer !== null) {
            return $viewer->canSeeNsfw();
        }

        return request()->cookie(NsfwConfirmationController::COOKIE) === '1';
    }

    private function viewer(): ?User
    {
        $viewer = auth()->user();

        return $viewer instanceof User ? $viewer : null;
    }
}
