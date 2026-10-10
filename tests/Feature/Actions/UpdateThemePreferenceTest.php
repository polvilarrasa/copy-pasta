<?php

declare(strict_types=1);

use App\Actions\UpdateThemePreference;
use App\Enums\EventType;
use App\Enums\Theme;
use App\Models\TrackedEvent;
use App\Models\User;

test('saves the chosen theme on the account and records a theme_change event', function (): void {
    $user = User::factory()->create(['theme' => Theme::System]);

    app(UpdateThemePreference::class)->handle(Theme::Dark, $user);

    expect($user->refresh()->theme)->toBe(Theme::Dark)
        ->and(TrackedEvent::query()->where('type', EventType::ThemeChange)->where('user_id', $user->id)->count())->toBe(1);
});

test('records the theme_change event for an anonymous visitor without touching any account', function (): void {
    $users = User::query()->count();

    app(UpdateThemePreference::class)->handle(Theme::Light);

    expect(User::query()->count())->toBe($users)
        ->and(TrackedEvent::query()->where('type', EventType::ThemeChange)->whereNull('user_id')->count())->toBe(1);
});
