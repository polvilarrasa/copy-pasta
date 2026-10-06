<?php

declare(strict_types=1);

use App\Enums\Theme;
use App\Models\User;
use App\Support\ThemePreference;

test('an anonymous visitor keeps the chosen theme in a one year cookie', function (): void {
    $response = $this->post(route('theme.update'), ['theme' => 'dark']);

    $cookie = collect($response->headers->getCookies())
        ->firstWhere(fn ($cookie): bool => $cookie->getName() === ThemePreference::COOKIE);

    expect($cookie)->not->toBeNull()
        ->and($cookie->getExpiresTime() - time())->toBeGreaterThan(364 * 86400);
});

test('a member keeps the theme on the account and sets no cookie', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('theme.update'), ['theme' => 'light']);

    expect($user->refresh()->theme)->toBe(Theme::Light)
        ->and(collect($response->headers->getCookies())->firstWhere(fn ($cookie): bool => $cookie->getName() === ThemePreference::COOKIE))->toBeNull();
});

test('rejects a theme outside system, light and dark without changing anything', function (): void {
    $user = User::factory()->create(['theme' => Theme::System]);

    $this->actingAs($user)->post(route('theme.update'), ['theme' => 'neon'])
        ->assertSessionHasErrors('theme');

    expect($user->refresh()->theme)->toBe(Theme::System);
});

test('the account theme wins over the cookie', function (): void {
    $user = User::factory()->create(['theme' => Theme::Dark]);

    $this->actingAs($user)->withCookie(ThemePreference::COOKIE, 'light')
        ->get(route('home'))
        ->assertSee('data-theme="dark"', false);
});

test('renders no data-theme attribute when the theme is system', function (): void {
    $this->get(route('home'))
        ->assertDontSee('data-theme', false);
});

test('renders data-theme for an anonymous visitor who chose light', function (): void {
    $this->withCookie(ThemePreference::COOKIE, 'light')
        ->get(route('home'))
        ->assertSee('<html lang="es" data-theme="light"', false);
});
