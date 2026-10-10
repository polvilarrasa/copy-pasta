<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WelcomeController extends Controller
{
    /**
     * The welcome screen, or with `?modo=editar` the screen to edit the favorite tags.
     */
    public function show(Request $request): Response
    {
        return response()->view('public.welcome', ['editing' => $request->query('modo') === 'editar']);
    }
}
