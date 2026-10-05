<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\ResolveUsername;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    /**
     * A name that changed within the last 90 days redirects to its current name. The profile page itself comes in
     * Phase 16; until then the current name answers 404.
     */
    public function show(string $username, ResolveUsername $resolveUsername): RedirectResponse
    {
        $currentUsername = $resolveUsername->handle($username);

        abort_if($currentUsername === null || $currentUsername === $username, 404);

        return redirect()->route('profile.show', $currentUsername, 301);
    }
}
