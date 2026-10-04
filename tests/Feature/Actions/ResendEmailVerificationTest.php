<?php

declare(strict_types=1);

use App\Actions\ResendEmailVerification;
use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Notification;

test('un admin reenvía la verificación a un miembro sin verificar', function (): void {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $member = User::factory()->unverified()->create();

    expect(app(ResendEmailVerification::class)->handle($admin, $member))->toBeTrue();

    Notification::assertSentTo($member, VerifyEmail::class);
    expect(ModerationAction::query()->where('action', ModerationActionType::VerificationResent)->count())->toBe(1);
});

test('no reenvía nada a un miembro que ya está verificado', function (): void {
    Notification::fake();
    $admin = User::factory()->admin()->create();

    expect(app(ResendEmailVerification::class)->handle($admin, User::factory()->create()))->toBeFalse();

    Notification::assertNothingSent();
});

test('no reenvía más de tres veces por hora al mismo miembro', function (): void {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $member = User::factory()->unverified()->create();

    foreach (range(1, ResendEmailVerification::MAX_RESENDS_PER_HOUR) as $_) {
        app(ResendEmailVerification::class)->handle($admin, $member);
    }

    app(ResendEmailVerification::class)->handle($admin, $member);
})->throws(ThrottleRequestsException::class);
