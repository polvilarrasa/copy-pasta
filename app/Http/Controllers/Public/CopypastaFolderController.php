<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\AddToFolder;
use App\Actions\CreateFolder;
use App\Actions\SyncCopypastaFolders;
use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use App\Support\EventContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CopypastaFolderController extends Controller
{
    /**
     * Lists the member's folders with a flag for the ones that already hold the copy-pasta.
     */
    public function index(Request $request, Copypasta $copypasta): JsonResponse
    {
        $member = $this->member($request);

        abort_unless(Gate::allows('view', $copypasta), 404);

        Folder::ensureDefaultFor($member);

        $holdingIds = $copypasta->folders()->where('folders.user_id', $member->getKey())->pluck('folders.id')->all();

        $folders = $member->folders()
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(fn (Folder $folder): array => $this->folderPayload($folder, in_array($folder->getKey(), $holdingIds, false)));

        return response()->json(['folders' => $folders]);
    }

    /**
     * Makes the checked folders the ones that hold the copy-pasta. Folders the member does not own are rejected.
     */
    public function update(Request $request, Copypasta $copypasta, SyncCopypastaFolders $syncCopypastaFolders): JsonResponse
    {
        $member = $this->member($request);

        $validated = $request->validate([
            'folder_ids' => ['present', 'array'],
            'folder_ids.*' => ['integer', Rule::exists(Folder::class, 'id')->where('user_id', $member->getKey())],
        ]);

        $syncCopypastaFolders->handle($member, $copypasta, $validated['folder_ids'], EventContext::fromRequest($request));

        return response()->json($this->favoriteState($member, $copypasta));
    }

    /**
     * Quick creation from the selector: creates the folder and puts the copy-pasta in it in one step.
     */
    public function store(Request $request, Copypasta $copypasta, CreateFolder $createFolder, AddToFolder $addToFolder): JsonResponse
    {
        $member = $this->member($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        $folder = DB::transaction(function () use ($member, $copypasta, $validated, $createFolder, $addToFolder, $request): Folder {
            $folder = $createFolder->handle($member, $validated['name']);

            $addToFolder->handle($member, $folder, $copypasta, EventContext::fromRequest($request));

            return $folder;
        });

        return response()->json([
            'folder' => $this->folderPayload($folder, true),
            ...$this->favoriteState($member, $copypasta),
        ], 201);
    }

    /**
     * @return array{id: int, name: string, is_default: bool, contains: bool}
     */
    private function folderPayload(Folder $folder, bool $contains): array
    {
        return [
            'id' => $folder->getKey(),
            'name' => $folder->name,
            'is_default' => $folder->is_default,
            'contains' => $contains,
        ];
    }

    /**
     * @return array{favorited: bool, favorites_count: int}
     */
    private function favoriteState(User $member, Copypasta $copypasta): array
    {
        $copypasta->refresh();

        return [
            'favorited' => $copypasta->folders()
                ->where('folders.user_id', $member->getKey())
                ->where('folders.is_default', true)
                ->exists(),
            'favorites_count' => $copypasta->favorites_count,
        ];
    }

    private function member(Request $request): User
    {
        $user = $request->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
