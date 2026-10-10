<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\RecordCopypastaVisit;
use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use App\Models\User;
use App\Support\CrawlerDetector;
use App\Support\EventContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CopypastaController extends Controller
{
    /**
     * Crawlers and link-preview bots see the page but leave no detail view, so they add no views and attribute no ref.
     */
    public function show(
        Request $request,
        Copypasta $copypasta,
        RecordCopypastaVisit $recordVisit,
        CrawlerDetector $crawlerDetector,
        ?string $slug = null,
    ): RedirectResponse|Response {
        abort_unless(Gate::allows('view', $copypasta), 404);

        if ($slug !== $copypasta->slug) {
            return redirect()->route('copypastas.show', [$copypasta, $copypasta->slug, ...$request->query()], 301);
        }

        $viewer = $request->user();

        $copypasta = Copypasta::query()
            ->withViewerState($viewer instanceof User ? $viewer : null)
            ->with(['user:id,username,title_key,anonymized_at,banned_at', 'tags:id,name,slug,color', 'revisions'])
            ->findOrFail($copypasta->getKey());

        $context = EventContext::fromRequest($request);

        if (! $crawlerDetector->isBot($request)) {
            $recordVisit->handle($copypasta, $viewer instanceof User ? $viewer : null, $context);
        }

        return response()->view('public.copypasta', ['copypasta' => $copypasta, 'context' => $context]);
    }
}
