<?php

declare(strict_types=1);

use App\Actions\AnonymizeUser;
use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;

test('anonimiza las cuentas borradas por un admin hace más de 30 días', function (): void {
    $member = User::factory()->create();
    $member->forceFill(['deleted_at' => now()->subDays(31)])->save();

    $this->artisan('users:anonymize-expired')->assertSuccessful();

    expect($member->refresh()->anonymized_at)->not->toBeNull();
});

test('no toca las cuentas borradas hace menos de 30 días', function (): void {
    $member = User::factory()->create();
    $member->forceFill(['deleted_at' => now()->subDays(29)])->save();

    $this->artisan('users:anonymize-expired')->assertSuccessful();

    expect($member->refresh()->anonymized_at)->toBeNull();
});

test('no toca las cuentas activas', function (): void {
    $member = User::factory()->create();

    $this->artisan('users:anonymize-expired')->assertSuccessful();

    expect($member->refresh()->anonymized_at)->toBeNull()
        ->and($member->trashed())->toBeFalse();
});

test('registra la anonimización automática sin actor', function (): void {
    $member = User::factory()->create();
    $member->forceFill(['deleted_at' => now()->subDays(31)])->save();

    $this->artisan('users:anonymize-expired')->assertSuccessful();

    expect(ModerationAction::query()->where('action', ModerationActionType::UserAnonymized)->sole())
        ->actor_id->toBeNull();
});

test('no vuelve a anonimizar una cuenta ya anonimizada', function (): void {
    $member = User::factory()->create();
    $member->forceFill(['deleted_at' => now()->subDays(31)])->save();
    app(AnonymizeUser::class)->handle($member->refresh(), null);

    $this->artisan('users:anonymize-expired')->assertSuccessful();

    expect(ModerationAction::query()->where('action', ModerationActionType::UserAnonymized)->count())->toBe(1);
});
