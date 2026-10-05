<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\RecordCopypastaCopy;
use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use App\Models\User;
use App\Support\EventContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CopypastaCopyController extends Controller
{
    public function __invoke(Copypasta $copypasta, Request $request, RecordCopypastaCopy $recordCopy): Response
    {
        abort_unless(Gate::allows('view', $copypasta), 404);

        $user = $request->user();

        $recordCopy->handle(
            $copypasta,
            (string) $request->ip(),
            $user instanceof User ? $user : null,
            EventContext::fromRequest($request),
        );

        return response()->noContent();
    }
}
