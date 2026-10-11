<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Testing\TestResponse;

function signInWith(object $test, User $user, ?string $intended = null): TestResponse
{
    if ($intended !== null) {
        $test->withSession(['url.intended' => $intended]);
    }

    $response = $test->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    if ($user->hasEnabledTwoFactorAuthentication()) {
        $response = $test->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1']);
    }

    return $response;
}

test('un admin con 2FA entra a /admin tras el login', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();

    signInWith($this, $admin)->assertRedirect('/admin');
});

test('un moderador con 2FA entra a /admin tras el login cuando no pidió ninguna página', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();

    signInWith($this, $moderator)->assertRedirect('/admin');
});

test('el staff vuelve a la página que pedía, igual que cualquiera: la URL pedida gana siempre', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();

    signInWith($this, $moderator, url('/nuevos'))->assertRedirect(url('/nuevos'));
});

test('el staff que pidió una página de administración vuelve a ella', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();

    signInWith($this, $admin, url('/admin/users'))->assertRedirect(url('/admin/users'));
});

test('un moderador que comparte texto a /publicar sin sesión vuelve al formulario con los datos tras el 2FA', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $shared = ['title' => 'Título compartido', 'text' => 'Texto compartido desde otra app'];

    $this->get('/publicar?'.http_build_query($shared))->assertRedirect(route('login'));

    $location = (string) signInWith($this, $moderator)->assertRedirect()->headers->get('Location');

    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    expect(parse_url($location, PHP_URL_PATH))->toBe('/publicar')
        ->and($query)->toEqual($shared);
});

test('un miembro sin página pedida acaba en el feed', function (): void {
    $member = User::factory()->create();

    signInWith($this, $member)->assertRedirect(route('home'));
});

test('un miembro vuelve a la página que pedía', function (): void {
    $member = User::factory()->create();

    signInWith($this, $member, url('/nuevos'))->assertRedirect(url('/nuevos'));
});

test('un admin sin 2FA llega a la configuración de seguridad sin bucle de redirecciones', function (): void {
    $admin = User::factory()->admin()->create();

    signInWith($this, $admin)->assertRedirect('/admin');

    $this->get('/admin')->assertRedirect(route('security.edit'));

    $this->get(route('security.edit'))->assertRedirect(route('password.confirm'));
});
