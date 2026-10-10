<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreateFolder;
use App\Actions\DeleteFolder;
use App\Actions\RenameFolder;
use App\Actions\UpdateFolderDescription;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class FoldersGrid extends Component
{
    public string $newFolderName = '';

    public ?int $editingFolderId = null;

    public string $editingName = '';

    public string $editingDescription = '';

    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Folder::class);

        Folder::ensureDefaultFor($this->user());
    }

    public function createFolder(): void
    {
        app(CreateFolder::class)->handle($this->user(), $this->newFolderName);

        $this->newFolderName = '';
    }

    public function startEdit(int $folderId): void
    {
        $folder = $this->ownFolder($folderId);

        $this->editingFolderId = $folder->getKey();
        $this->editingName = $folder->name;
        $this->editingDescription = (string) $folder->description;
    }

    public function saveEdit(): void
    {
        if ($this->editingFolderId === null) {
            return;
        }

        $folder = $this->ownFolder($this->editingFolderId);
        $user = $this->user();

        if (! $folder->is_default && $this->editingName !== $folder->name) {
            app(RenameFolder::class)->handle($user, $folder, $this->editingName);
        }

        app(UpdateFolderDescription::class)->handle($user, $folder, $this->editingDescription);

        $this->editingFolderId = null;
    }

    public function resetEdit(): void
    {
        $this->editingFolderId = null;
    }

    public function confirmDelete(int $folderId): void
    {
        $this->confirmingDeleteId = $folderId;
    }

    public function deleteFolder(): void
    {
        if ($this->confirmingDeleteId === null) {
            return;
        }

        app(DeleteFolder::class)->handle($this->user(), $this->ownFolder($this->confirmingDeleteId));

        $this->confirmingDeleteId = null;
    }

    public function moveUp(int $folderId): void
    {
        $this->swapWithNeighbor($folderId, -1);
    }

    public function moveDown(int $folderId): void
    {
        $this->swapWithNeighbor($folderId, 1);
    }

    public function render(): View
    {
        return view('livewire.folders-grid', [
            'folders' => $this->folders(),
        ]);
    }

    private function ownFolder(int $folderId): Folder
    {
        return $this->user()->folders()->whereKey($folderId)->firstOrFail();
    }

    /**
     * @return EloquentCollection<int, Folder>
     */
    private function folders(): EloquentCollection
    {
        return $this->user()->folders()
            ->withCount('copypastas')
            ->with(['copypastas' => fn ($query) => $query->orderByDesc('copypasta_folder.created_at')->limit(1)])
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    private function user(): User
    {
        $user = Auth::user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }

    private function swapWithNeighbor(int $folderId, int $direction): void
    {
        $ordered = $this->folders()->values();
        $index = $ordered->search(fn (Folder $folder): bool => $folder->getKey() === $folderId);

        if ($index === false) {
            return;
        }

        $neighborIndex = $index + $direction;

        if (! $ordered->has($neighborIndex)) {
            return;
        }

        $folder = $ordered->get($index);
        $neighbor = $ordered->get($neighborIndex);

        [$folderPosition, $neighborPosition] = [$folder->position, $neighbor->position];

        $folder->update(['position' => $neighborPosition]);
        $neighbor->update(['position' => $folderPosition]);
    }
}
