<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\CastVote;
use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use App\Models\User;
use App\Support\EventContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CopypastaVoteController extends Controller
{
    public function __invoke(Copypasta $copypasta, Request $request, CastVote $castVote): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'integer', Rule::in([1, -1])],
        ]);

        $user = $request->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        $myVote = $castVote->handle($user, $copypasta, (int) $validated['value'], EventContext::fromRequest($request));

        $copypasta->refresh();

        return response()->json([
            'score' => $copypasta->score,
            'upvotes_count' => $copypasta->upvotes_count,
            'downvotes_count' => $copypasta->downvotes_count,
            'my_vote' => $myVote,
        ]);
    }
}
