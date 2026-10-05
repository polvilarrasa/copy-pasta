<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\RecordCopypastaShare;
use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use App\Models\User;
use App\Support\EventContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CopypastaShareController extends Controller
{
    public function __invoke(Copypasta $copypasta, Request $request, RecordCopypastaShare $recordShare): JsonResponse
    {
        abort_unless(Gate::allows('view', $copypasta), 404);

        $user = $request->user();

        return response()->json([
            'ref' => $recordShare->handle(
                $copypasta,
                $user instanceof User ? $user : null,
                EventContext::fromRequest($request),
            ),
        ]);
    }
}
