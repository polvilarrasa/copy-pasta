<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class NsfwConfirmationController extends Controller
{
    public const COOKIE = 'nsfw_confirmed';

    private const COOKIE_MINUTES = 525600;

    public function __invoke(Request $request): RedirectResponse
    {
        $target = (string) $request->input('redirect', '/');

        if (! str_starts_with($target, '/') || str_starts_with($target, '//')) {
            $target = '/';
        }

        return redirect($target)->withCookie($this->consentCookie());
    }

    private function consentCookie(): Cookie
    {
        return cookie(self::COOKIE, '1', self::COOKIE_MINUTES);
    }
}
