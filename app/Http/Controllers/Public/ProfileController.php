<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\ResolveUsername;
use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    /**
     * A name that changed within the last 90 days redirects to its current name. Anonymized accounts never resolve,
     * and a banned account's current name still 404s: both are hidden from the public profile.
     */
    public function show(string $username, ResolveUsername $resolveUsername): RedirectResponse|Response
    {
        $currentUsername = $resolveUsername->handle($username);

        abort_if($currentUsername === null, 404);

        if ($currentUsername !== $username) {
            return redirect()->route('profile.show', $currentUsername, 301);
        }

        $profileUser = User::query()->where('username', $currentUsername)->firstOrFail();

        abort_if($profileUser->isBanned(), 404);

        $counters = Copypasta::query()
            ->where('user_id', $profileUser->getKey())
            ->visible()
            ->selectRaw('count(*) as published, coalesce(sum(copies_count), 0) as copies, coalesce(sum(upvotes_count), 0) as upvotes')
            ->first();

        return response()->view('public.profile', [
            'profileUser' => $profileUser,
            'counters' => $counters,
        ]);
    }
}
