<?php

declare(strict_types=1);

use App\Actions\CreateUserByAdmin;
use App\Enums\Role;
use App\Mail\UserInvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Creates a member through the admin flow and returns the invitation link from the rendered email.
 */
function invitationLinkFor(User $member): string
{
    preg_match('/href="([^"]*\/invitacion\/[^"]*)"/', (new UserInvitationMail($member))->render(), $matches);

    return html_entity_decode($matches[1]);
}

test('un admin crea un usuario sin verificar y le envía la invitación por email', function (): void {
    Mail::fake();
    $admin = User::factory()->admin()->create();

    $member = app(CreateUserByAdmin::class)->handle($admin, 'nuevo', 'nuevo@example.com', Role::User);

    expect($member->email_verified_at)->toBeNull();
    Mail::assertQueued(UserInvitationMail::class, fn (UserInvitationMail $mail): bool => $mail->hasTo('nuevo@example.com'));
});

test('la cuenta recién creada no se puede usar para entrar antes de aceptar la invitación', function (): void {
    $admin = User::factory()->admin()->create();
    Mail::fake();
    $member = app(CreateUserByAdmin::class)->handle($admin, 'nuevo', 'nuevo@example.com', Role::User);

    expect(Auth::attempt(['email' => 'nuevo@example.com', 'password' => 'password']))->toBeFalse()
        ->and(Hash::check('password', $member->password))->toBeFalse();
});

test('la invitación abre el formulario y elegir contraseña verifica el email', function (): void {
    $member = User::factory()->unverified()->create();

    $this->get(invitationLinkFor($member))->assertOk();

    $this->post(invitationLinkFor($member), [
        'password' => 'contraseña-segura-2026',
        'password_confirmation' => 'contraseña-segura-2026',
    ])->assertRedirect(route('login'));

    expect($member->refresh())
        ->hasVerifiedEmail()->toBeTrue()
        ->and(Hash::check('contraseña-segura-2026', $member->password))->toBeTrue();
});

test('la invitación deja de valer una vez elegida la contraseña', function (): void {
    $member = User::factory()->unverified()->create();
    $link = invitationLinkFor($member);

    $this->post($link, [
        'password' => 'contraseña-segura-2026',
        'password_confirmation' => 'contraseña-segura-2026',
    ]);

    $this->get($link)->assertNotFound();
});

test('la invitación caduca a las 72 horas', function (): void {
    $member = User::factory()->unverified()->create();
    $link = invitationLinkFor($member);

    $this->travel(73)->hours();

    $this->get($link)->assertForbidden();
});

test('una invitación con la firma alterada no abre el formulario', function (): void {
    $member = User::factory()->unverified()->create();
    $tampered = str_replace('fingerprint=', 'fingerprint=x', invitationLinkFor($member));

    $this->get($tampered)->assertForbidden();
});

test('una invitación firmada para otra contraseña no abre el formulario', function (): void {
    $member = User::factory()->unverified()->create();
    $staleLink = URL::temporarySignedRoute('invitation.show', now()->addHours(72), [
        'user' => $member->getKey(),
        'fingerprint' => 'huella-de-otra-contraseña',
    ]);

    $this->get($staleLink)->assertNotFound();
});

test('la contraseña elegida en la invitación debe cumplir la política', function (): void {
    $member = User::factory()->unverified()->create();

    $this->post(invitationLinkFor($member), [
        'password' => '123',
        'password_confirmation' => '123',
    ])->assertSessionHasErrors('password');
});

test('un usuario borrado lógicamente no puede entrar con sus credenciales', function (): void {
    $user = User::factory()->create(['email' => 'borrado@example.com']);
    $user->delete();

    expect(Auth::attempt(['email' => 'borrado@example.com', 'password' => 'password']))->toBeFalse();
});
