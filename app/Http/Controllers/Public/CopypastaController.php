<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\RecordEvent;
use App\Enums\EventType;
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
        RecordEvent $recordEvent,
        CrawlerDetector $crawlerDetector,
        ?string $slug = null,
    ): RedirectResponse|Response {
        abort_unless(Gate::allows('view', $copypasta), 404);

        if ($slug !== $copypasta->slug) {
            return redirect()->route('copypastas.show', [$copypasta, $copypasta->slug], 301);
        }

        $viewer = $request->user();

        $copypasta = Copypasta::query()
            ->withViewerState($viewer instanceof User ? $viewer : null)
            ->with(['user:id,username,anonymized_at,banned_at', 'tags:id,name,slug,color', 'revisions'])
            ->findOrFail($copypasta->getKey());

        $context = EventContext::fromRequest($request);

        if (! $crawlerDetector->isBot($request)) {
            $recordEvent->handle(
                EventType::DetailView,
                $viewer instanceof User ? $viewer : null,
                $copypasta,
                $context,
            );
        }

        return response()->view('public.copypasta', ['copypasta' => $copypasta, 'context' => $context]);
    }
}
