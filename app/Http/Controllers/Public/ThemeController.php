<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\UpdateThemePreference;
use App\Enums\Theme;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ThemePreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThemeController extends Controller
{
    public function __invoke(Request $request, UpdateThemePreference $updateThemePreference): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::enum(Theme::class)],
        ]);

        $theme = Theme::from($validated['theme']);
        $user = $request->user();
        $member = $user instanceof User ? $user : null;

        $updateThemePreference->handle($theme, $member);

        $response = redirect()->back();

        return $member === null ? $response->withCookie(ThemePreference::cookie($theme)) : $response;
    }
}
