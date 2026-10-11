<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CopypastaVariantController extends Controller
{
    /**
     * Any signed-in member reaches the page, like /publicar: the view shows the form, or the email-verification notice
     * when they cannot publish yet. Their own copy-pastas and hidden ones have no variant page. A deleted one is not
     * found by the route.
     */
    public function create(Request $request, Copypasta $copypasta): Response
    {
        abort_unless($copypasta->isAvailable() && $request->user()?->getKey() !== $copypasta->user_id, 404);

        return response()->view('public.copypasta-variant', ['copypasta' => $copypasta]);
    }
}
