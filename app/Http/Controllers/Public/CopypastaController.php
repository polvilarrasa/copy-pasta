<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CopypastaController extends Controller
{
    public function show(Request $request, Copypasta $copypasta, ?string $slug = null): RedirectResponse|Response
    {
        abort_unless(Gate::allows('view', $copypasta), 404);

        if ($slug !== $copypasta->slug) {
            return redirect()->route('copypastas.show', [$copypasta, $copypasta->slug], 301);
        }

        $viewer = $request->user();

        $copypasta = Copypasta::query()
            ->withViewerState($viewer instanceof User ? $viewer : null)
            ->with(['user:id,username,anonymized_at', 'tags:id,name,slug,color', 'revisions'])
            ->findOrFail($copypasta->getKey());

        return response()->view('public.copypasta', ['copypasta' => $copypasta]);
    }
}
