<?php

declare(strict_types=1);

use App\Actions\ImpersonateUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passkeys\Passkey;
use Livewire\Livewire;

function passkeyFor(User $user): Passkey
{
    return Passkey::query()->forceCreate([
        'user_id' => $user->getKey(),
        'name' => 'Mac',
        'credential_id' => Str::random(32),
        'credential' => ['type' => 'public-key'],
    ]);
}

/**
 * Starts a real impersonation and simulates a password confirmation left over from the admin's own session, the
 * case the confirmation timestamp used to let through.
 *
 * @return array{0: User, 1: User}
 */
function impersonatingWithLeftoverConfirmation(): array
{
    $admin = User::factory()->admin()->create();
    $member = User::factory()->withTwoFactor()->create(['password' => Hash::make('contraseña-actual')]);

    test()->actingAs($admin);
    app(ImpersonateUser::class)->handle($admin, $member);
    session(['auth.password_confirmed_at' => now()->timestamp]);

    return [$admin, $member];
}

test('un admin que actúa como miembro no llega a las rutas de 2FA y passkeys', function (string $method, string $uri): void {
    impersonatingWithLeftoverConfirmation();

    $this->call($method, $uri)->assertForbidden();
})->with([
    'activar 2FA' => ['POST', '/user/two-factor-authentication'],
    'desactivar 2FA' => ['DELETE', '/user/two-factor-authentication'],
    'confirmar 2FA' => ['POST', '/user/confirmed-two-factor-authentication'],
    'QR de 2FA' => ['GET', '/user/two-factor-qr-code'],
    'clave de 2FA' => ['GET', '/user/two-factor-secret-key'],
    'ver códigos de recuperación' => ['GET', '/user/two-factor-recovery-codes'],
    'regenerar códigos de recuperación' => ['POST', '/user/two-factor-recovery-codes'],
    'opciones para registrar passkey' => ['GET', '/user/passkeys/options'],
    'registrar passkey' => ['POST', '/user/passkeys'],
    'opciones para confirmar con passkey' => ['GET', '/passkeys/confirm/options'],
    'confirmar con passkey' => ['POST', '/passkeys/confirm'],
]);

test('un admin que actúa como miembro no puede borrar una passkey del miembro', function (): void {
    [, $member] = impersonatingWithLeftoverConfirmation();
    $passkey = passkeyFor($member);

    $this->delete('/user/passkeys/'.$passkey->getKey())->assertForbidden();

    expect($passkey->exists())->toBeTrue();
});

test('el miembro sin impersonación sí usa sus rutas de 2FA', function (): void {
    $member = User::factory()->withTwoFactor()->create();

    $this->actingAs($member)
        ->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->get('/user/two-factor-qr-code')
        ->assertOk();
});

test('durante la impersonación no se muestran los códigos de recuperación del miembro', function (): void {
    impersonatingWithLeftoverConfirmation();

    Livewire::test('pages::settings.two-factor.recovery-codes')
        ->assertSet('recoveryCodes', []);
});

test('durante la impersonación borrar la cuenta del miembro está bloqueado', function (): void {
    [, $member] = impersonatingWithLeftoverConfirmation();

    Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'contraseña-actual')
        ->call('deleteUser');

    expect($member->fresh()->trashed())->toBeFalse();
});

test('fuera de la impersonación el miembro sí puede borrar su cuenta', function (): void {
    $member = User::factory()->create(['password' => Hash::make('contraseña-actual')]);

    $this->actingAs($member);

    Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'contraseña-actual')
        ->call('deleteUser');

    expect($member->fresh()->trashed())->toBeTrue();
});

test('durante la impersonación el modal de 2FA no muestra la clave del miembro', function (): void {
    impersonatingWithLeftoverConfirmation();

    Livewire::test('pages::settings.two-factor-setup-modal', ['requiresConfirmation' => false])
        ->call('startTwoFactorSetup')
        ->assertSet('manualSetupKey', '')
        ->assertSet('qrCodeSvg', '');
});

test('durante la impersonación no se regeneran los códigos de recuperación', function (): void {
    [, $member] = impersonatingWithLeftoverConfirmation();
    $codesBefore = $member->fresh()->two_factor_recovery_codes;

    Livewire::test('pages::settings.two-factor.recovery-codes')
        ->call('regenerateRecoveryCodes');

    expect($member->fresh()->two_factor_recovery_codes)->toBe($codesBefore);
});

test('durante la impersonación no se desactiva el 2FA del miembro', function (): void {
    [, $member] = impersonatingWithLeftoverConfirmation();

    Livewire::test('pages::settings.security')->call('disable');

    expect($member->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('durante la impersonación no se borra una passkey desde ajustes', function (): void {
    [, $member] = impersonatingWithLeftoverConfirmation();
    $passkey = passkeyFor($member);

    Livewire::test('pages::settings.security')->call('deletePasskey', $passkey->getKey());

    expect($passkey->exists())->toBeTrue();
});

test('el admin impersonando sigue sin poder cambiar la contraseña desde ajustes', function (): void {
    [, $member] = impersonatingWithLeftoverConfirmation();
    $passwordBefore = $member->fresh()->password;

    Livewire::test('pages::settings.security')
        ->set('current_password', 'contraseña-actual')
        ->set('password', 'nueva-contraseña-segura-2026')
        ->set('password_confirmation', 'nueva-contraseña-segura-2026')
        ->call('updatePassword');

    expect($member->fresh()->password)->toBe($passwordBefore);
});
