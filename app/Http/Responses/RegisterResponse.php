<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Actions\ListActiveTags;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * A new member lands on the welcome screen, where they pick their favorite tags. Showing it counts as offering it. With too few active tags to choose from it is skipped.
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        if (! app(ListActiveTags::class)->canOfferOnboarding()) {
            return redirect()->route('home');
        }

        $user = $request->user();

        if ($user instanceof User && $user->onboarded_at === null) {
            $user->forceFill(['onboarded_at' => now()])->save();
        }

        return redirect()->route('welcome');
    }
}
