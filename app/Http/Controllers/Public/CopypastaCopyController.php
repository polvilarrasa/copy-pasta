<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\RecordCopypastaCopy;
use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CopypastaCopyController extends Controller
{
    public function __invoke(Copypasta $copypasta, Request $request, RecordCopypastaCopy $recordCopy): Response
    {
        abort_unless(Gate::allows('view', $copypasta), 404);

        $recordCopy->handle($copypasta, (string) $request->ip());

        return response()->noContent();
    }
}
