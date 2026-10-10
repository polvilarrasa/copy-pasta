<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Theme;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class ThemePreference
{
    public const COOKIE = 'theme';

    private const COOKIE_MINUTES = 525600;

    public function __construct(private Request $request) {}

    /**
     * The account's choice wins; anonymous visitors use the cookie; everything else follows the system.
     */
    public function current(): Theme
    {
        $user = $this->request->user();

        if ($user instanceof User) {
            return $user->theme;
        }

        return Theme::tryFrom((string) $this->request->cookie(self::COOKIE)) ?? Theme::System;
    }

    /**
     * Attributes for the <html> element. "system" sets nothing, so the stylesheet's media query decides.
     */
    public function htmlAttributes(): string
    {
        $theme = $this->current();

        return $theme === Theme::System ? '' : 'data-theme="'.$theme->value.'"';
    }

    public static function cookie(Theme $theme): Cookie
    {
        return cookie(self::COOKIE, $theme->value, self::COOKIE_MINUTES);
    }
}
