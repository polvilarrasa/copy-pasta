<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\ReportCopypasta;
use App\Enums\ReportReason;
use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use App\Models\User;
use App\Support\EventContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CopypastaReportController extends Controller
{
    public function __invoke(Copypasta $copypasta, Request $request, ReportCopypasta $reportCopypasta): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        $reportCopypasta->handle(
            $user,
            $copypasta,
            ReportReason::from($validated['reason']),
            $validated['details'] ?? null,
            EventContext::fromRequest($request),
        );

        return response()->json(['message' => __('public.report.sent')], 201);
    }
}
