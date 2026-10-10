<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\RemoveFromFolder;
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

    public function mount(Folder $folder): void
    {
        abort_unless(Gate::allows('view', $folder), 404);

        $this->folder = $folder;
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
            ->with(['user:id,username,anonymized_at', 'tags:id,name,slug,color'])
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
