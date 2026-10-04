<?php

declare(strict_types=1);

use App\Actions\ImpersonateUser;
use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/**
 * Starts a real impersonation within the test session and returns the admin and the impersonated member.
 *
 * @return array{0: User, 1: User}
 */
function impersonating(): array
{
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create(['password' => Hash::make('contraseña-actual')]);

    test()->actingAs($admin);
    app(ImpersonateUser::class)->handle($admin, $member);

    return [$admin, $member];
}

test('la banda aparece en la web mientras se actúa como un miembro', function (): void {
    [, $member] = impersonating();

    $this->get('/')->assertSee('Estás actuando como @'.$member->username);
    $this->get('/app')->assertSee('Estás actuando como @'.$member->username);
    $this->get('/settings/profile')->assertSee('Estás actuando como @'.$member->username);
});

test('la banda no aparece fuera de una impersonación', function (): void {
    $this->actingAs(User::factory()->create())->get('/')->assertDontSee('Estás actuando como');
});

test('volver termina la impersonación y devuelve al admin al panel', function (): void {
    impersonating();

    $this->post(route('impersonation.leave'))->assertRedirect(url('/admin'));

    expect(is_impersonating())->toBeFalse()
        ->and(ModerationAction::query()->where('action', ModerationActionType::ImpersonateEnd)->exists())->toBeTrue();
});

test('durante la impersonación el email no cambia desde ajustes', function (): void {
    [, $member] = impersonating();
    $emailBefore = $member->email;

    Livewire::test('pages::settings.profile')
        ->set('email', 'nuevo@example.com')
        ->call('updateProfileInformation');

    expect($member->refresh()->email)->toBe($emailBefore);
});

test('durante la impersonación la contraseña no cambia desde ajustes', function (): void {
    [, $member] = impersonating();

    Livewire::test('pages::settings.security')
        ->set('current_password', 'contraseña-actual')
        ->set('password', 'nueva-contraseña-123')
        ->set('password_confirmation', 'nueva-contraseña-123')
        ->call('updatePassword');

    expect(Hash::check('contraseña-actual', $member->refresh()->password))->toBeTrue()
        ->and(Hash::check('nueva-contraseña-123', $member->password))->toBeFalse();
});

test('durante la impersonación ni el email ni la contraseña cambian desde ajustes', function (): void {
    [, $member] = impersonating();
    $emailBefore = $member->email;

    Livewire::test('pages::settings.profile')
        ->set('email', 'nuevo@example.com')
        ->call('updateProfileInformation');

    Livewire::test('pages::settings.security')
        ->set('current_password', 'contraseña-actual')
        ->set('password', 'nueva-contraseña-123')
        ->set('password_confirmation', 'nueva-contraseña-123')
        ->call('updatePassword');

    $member->refresh();

    expect($member->email)->toBe($emailBefore)
        ->and(Hash::check('contraseña-actual', $member->password))->toBeTrue();
});
