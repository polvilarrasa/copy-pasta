<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\DismissCopypasta;
use App\Actions\UndoDismissCopypasta;
use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use App\Models\User;
use App\Support\EventContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DismissController extends Controller
{
    public function store(Copypasta $copypasta, Request $request, DismissCopypasta $dismiss): JsonResponse
    {
        $dismiss->handle($this->user($request), $copypasta, EventContext::fromRequest($request));

        return response()->json(['dismissed' => true]);
    }

    public function destroy(Copypasta $copypasta, Request $request, UndoDismissCopypasta $undo): JsonResponse
    {
        $undo->handle($this->user($request), $copypasta, EventContext::fromRequest($request));

        return response()->json(['dismissed' => false]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
