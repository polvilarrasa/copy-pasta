<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\RemoveFromFolder;
use App\Actions\SetFolderVisibility;
use App\Actions\ShareFolder;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class FolderDetail extends Component
{
    public Folder $folder;

    public string $search = '';

    public bool $isPublic = false;

    public function mount(Folder $folder): void
    {
        abort_unless(Gate::allows('view', $folder), 404);

        $this->folder = $folder;
        $this->isPublic = (bool) $folder->is_public;
    }

    public function togglePublic(): void
    {
        app(SetFolderVisibility::class)->handle($this->user(), $this->folder, ! (bool) $this->folder->is_public);

        $this->isPublic = (bool) $this->folder->is_public;
    }

    /**
     * Returns the public address, making the folder public first when it was private.
     */
    public function shareFolder(): string
    {
        $url = app(ShareFolder::class)->handle($this->user(), $this->folder);

        $this->isPublic = (bool) $this->folder->is_public;

        return $url;
    }

    public function removeFromFolder(string $copypastaId): void
    {
        $copypasta = Copypasta::withTrashed()->whereKey($copypastaId)->firstOrFail();

        app(RemoveFromFolder::class)->handle($this->user(), $this->folder, $copypasta);
    }

    public function render(): View
    {
        return view('livewire.folder-detail', [
            'copypastas' => $this->copypastas(),
            'copypastasCount' => $this->folder->copypastas()->withTrashed()->count(),
            'hasAnyItems' => $this->folder->copypastas()->withTrashed()->exists(),
        ]);
    }

    /**
     * @return EloquentCollection<int, Copypasta>
     */
    private function copypastas(): EloquentCollection
    {
        return $this->folder->copypastas()
            ->withTrashed()
            ->withViewerState($this->user())
            ->when(trim($this->search) !== '', fn ($query) => $query->where('title', 'ilike', '%'.$this->search.'%'))
            ->with(['user:id,username,title_key,anonymized_at,banned_at', 'tags:id,name,slug,color'])
            ->orderByDesc('copypasta_folder.created_at')
            ->get();
    }

    private function user(): User
    {
        $user = Auth::user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
