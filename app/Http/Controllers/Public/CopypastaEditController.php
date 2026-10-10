<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CopypastaEditController extends Controller
{
    public function edit(Copypasta $copypasta): Response
    {
        abort_unless(Gate::allows('update', $copypasta), 404);

        return response()->view('public.copypasta-edit', ['copypasta' => $copypasta]);
    }
}
