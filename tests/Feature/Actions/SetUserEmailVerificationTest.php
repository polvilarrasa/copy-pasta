<?php

declare(strict_types=1);

use App\Actions\SetUserEmailVerification;
use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('un admin marca el email de un miembro como verificado y queda en el log', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->unverified()->create();

    app(SetUserEmailVerification::class)->handle($admin, $member, true);

    expect($member->refresh()->hasVerifiedEmail())->toBeTrue()
        ->and(ModerationAction::query()->where('action', ModerationActionType::EmailVerified)->count())->toBe(1);
});

test('un admin quita la verificación de un miembro', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    app(SetUserEmailVerification::class)->handle($admin, $member, false);

    expect($member->refresh()->hasVerifiedEmail())->toBeFalse()
        ->and(ModerationAction::query()->where('action', ModerationActionType::EmailUnverified)->count())->toBe(1);
});

test('pedir el estado que ya tiene no escribe en el log', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    app(SetUserEmailVerification::class)->handle($admin, $member, true);

    expect(ModerationAction::query()->count())->toBe(0);
});

test('un admin no cambia la verificación de su propia cuenta', function (): void {
    $admin = User::factory()->admin()->create();

    app(SetUserEmailVerification::class)->handle($admin, $admin, false);
})->throws(AuthorizationException::class);
