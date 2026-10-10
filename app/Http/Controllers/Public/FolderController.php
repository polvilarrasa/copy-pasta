<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FolderController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Folder::class);

        return response()->view('public.folders.index');
    }

    public function show(Folder $folder): Response
    {
        abort_unless(Gate::allows('view', $folder), 404);

        return response()->view('public.folders.show', ['folder' => $folder]);
    }
}
