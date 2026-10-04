<?php

declare(strict_types=1);

use App\Actions\SendPasswordReset;
use App\Enums\ModerationActionType;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('un admin envía el enlace estándar de reset y lo registra, sin tocar la contraseña', function (): void {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create(['password' => Hash::make('contraseña-original')]);
    $passwordBefore = $member->password;

    expect(app(SendPasswordReset::class)->handle($admin, $member))->toBeTrue();

    Notification::assertSentTo($member, ResetPassword::class);

    expect($member->refresh()->password)->toBe($passwordBefore);

    $this->assertDatabaseHas('moderation_actions', [
        'actor_id' => $admin->id,
        'action' => ModerationActionType::SendPasswordReset->value,
        'subject_id' => $member->id,
    ]);
});

test('un moderador no puede enviar resets', function (): void {
    Notification::fake();

    app(SendPasswordReset::class)->handle(User::factory()->moderator()->create(), User::factory()->create());
})->throws(AuthorizationException::class);
