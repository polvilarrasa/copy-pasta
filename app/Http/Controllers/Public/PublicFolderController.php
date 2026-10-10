<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use Illuminate\Http\Response;

class PublicFolderController extends Controller
{
    /**
     * A public folder is visible to everyone while it stays public and its owner has an active account. A private
     * folder, an unknown id and a folder of a banned, deleted or anonymized owner all answer 404.
     */
    public function show(string $publicId): Response
    {
        $folder = Folder::query()
            ->public()
            ->where('public_id', $publicId)
            ->with('user:id,username,title_key,anonymized_at,banned_at,deleted_at')
            ->first();

        abort_if($folder === null || $folder->user === null || $folder->user->isBanned() || $folder->user->isAnonymized(), 404);

        return response()->view('public.folders.public', ['folder' => $folder, 'owner' => $folder->user]);
    }
}
