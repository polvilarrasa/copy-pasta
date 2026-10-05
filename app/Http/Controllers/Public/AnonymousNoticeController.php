<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\ReportCopypasta;
use App\Enums\ReportReason;
use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnonymousNoticeController extends Controller
{
    public function create(Copypasta $copypasta): View
    {
        abort_unless($copypasta->published_at !== null && ! $copypasta->isHidden(), 404);

        return view('public.aviso', ['copypasta' => $copypasta]);
    }

    public function store(Request $request, Copypasta $copypasta, ReportCopypasta $reportCopypasta): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        $reportCopypasta->handleAnonymous(
            (string) $validated['email'],
            $copypasta,
            ReportReason::from((string) $validated['reason']),
            $validated['details'] ?? null,
        );

        return redirect()->route('notice.create', $copypasta)->with('status', __('public.notice.sent'));
    }
}
