<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MyCopypastasController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewOwn', Copypasta::class);

        return response()->view('public.my-copypastas');
    }
}
