<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

/**
 * Used after a password login and after a two-factor challenge, so both end in the same place.
 */
class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    /**
     * Everyone returns to the page they asked for, staff included (a shared text that sent them to log in, for
     * instance). Without one, staff go to the admin panel and everyone else to the feed. Staff without two-factor
     * authentication are sent to set it up by the panel's own middleware.
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();

        return redirect()->intended(
            $user instanceof User && $user->isStaff() ? Filament::getPanel('admin')->getUrl() : route('home'),
        );
    }
}
