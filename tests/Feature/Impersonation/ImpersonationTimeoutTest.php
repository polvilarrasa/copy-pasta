<?php

declare(strict_types=1);

use App\Actions\ImpersonateUser;
use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Lab404\Impersonate\Services\ImpersonateManager;

/**
 * Starts a real impersonation within the test session and returns the admin and the impersonated member.
 *
 * @return array{0: User, 1: User}
 */
function startImpersonation(): array
{
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create(['password' => Hash::make('contraseña-actual')]);

    test()->actingAs($admin);
    app(ImpersonateUser::class)->handle($admin, $member);

    return [$admin, $member];
}

test('la impersonación sigue activa antes de los 30 minutos', function (): void {
    startImpersonation();

    $this->travel(29)->minutes();
    $this->get('/');

    expect(app(ImpersonateManager::class)->isImpersonating())->toBeTrue();
});

test('la impersonación termina a los 30 minutos y devuelve al admin a su sesión', function (): void {
    [$admin] = startImpersonation();

    $this->travel(31)->minutes();
    $this->get('/');

    expect(app(ImpersonateManager::class)->isImpersonating())->toBeFalse()
        ->and(auth()->id())->toBe($admin->getKey());
});

test('el fin por caducidad queda registrado con su motivo', function (): void {
    startImpersonation();

    $this->travel(31)->minutes();
    $this->get('/');

    expect(ModerationAction::query()->where('action', ModerationActionType::ImpersonateEnd)->sole()->meta)
        ->toEqual(['reason' => 'expired']);
});

test('una impersonación sin hora de inicio se considera caducada', function (): void {
    startImpersonation();
    session()->forget(ImpersonateUser::STARTED_AT_KEY);

    $this->get('/');

    expect(app(ImpersonateManager::class)->isImpersonating())->toBeFalse();
});
