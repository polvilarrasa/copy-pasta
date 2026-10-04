<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CopypastaController extends Controller
{
    public function show(Copypasta $copypasta, ?string $slug = null): RedirectResponse|Response
    {
        abort_unless(Gate::allows('view', $copypasta), 404);

        if ($slug !== $copypasta->slug) {
            return redirect()->route('copypastas.show', [$copypasta, $copypasta->slug], 301);
        }

        $copypasta->load(['user:id,username', 'tags:id,name,slug,color']);

        return response()->view('public.copypasta', ['copypasta' => $copypasta]);
    }
}
