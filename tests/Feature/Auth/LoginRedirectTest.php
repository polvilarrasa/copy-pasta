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

test('un moderador con 2FA entra a /admin tras el login, aunque pidiera otra página', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();

    signInWith($this, $moderator, url('/nuevos'))->assertRedirect('/admin');
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
