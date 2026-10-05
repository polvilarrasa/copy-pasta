<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\ToggleFavorite;
use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use App\Models\User;
use App\Support\EventContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CopypastaFavoriteController extends Controller
{
    public function __invoke(Copypasta $copypasta, Request $request, ToggleFavorite $toggleFavorite): JsonResponse
    {
        $user = $request->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        $favorited = $toggleFavorite->handle($user, $copypasta, EventContext::fromRequest($request));

        $copypasta->refresh();

        return response()->json([
            'favorited' => $favorited,
            'favorites_count' => $copypasta->favorites_count,
        ]);
    }
}
