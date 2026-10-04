<?php

declare(strict_types=1);

use App\Actions\ImpersonateUser;
use App\Actions\StopImpersonating;
use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Lab404\Impersonate\Services\ImpersonateManager;

test('un admin actúa como un miembro y el inicio queda registrado', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $this->actingAs($admin);

    expect(app(ImpersonateUser::class)->handle($admin, $member))->toBeTrue()
        ->and(auth()->id())->toBe($member->id)
        ->and(is_impersonating())->toBeTrue();

    $this->assertDatabaseHas('moderation_actions', [
        'actor_id' => $admin->id,
        'action' => ModerationActionType::ImpersonateStart->value,
        'subject_id' => $member->id,
    ]);
});

test('al terminar la impersonación se restaura la sesión del admin y el fin queda registrado', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $this->actingAs($admin);
    app(ImpersonateUser::class)->handle($admin, $member);

    expect(app(StopImpersonating::class)->handle(app(ImpersonateManager::class)))->toBeTrue()
        ->and(auth()->id())->toBe($admin->id)
        ->and(is_impersonating())->toBeFalse();

    $end = ModerationAction::query()->where('action', ModerationActionType::ImpersonateEnd)->firstOrFail();

    expect($end->actor_id)->toBe($admin->id)
        ->and($end->subject_id)->toBe((string) $member->id);
});

test('un moderador no puede impersonar', function (): void {
    app(ImpersonateUser::class)->handle(User::factory()->moderator()->create(), User::factory()->create());
})->throws(AuthorizationException::class);

test('un admin no puede impersonar a un miembro del staff', function (): void {
    app(ImpersonateUser::class)->handle(User::factory()->admin()->create(), User::factory()->moderator()->create());
})->throws(AuthorizationException::class);

test('no se puede impersonar a otro usuario mientras ya se actúa como uno', function (): void {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    app(ImpersonateUser::class)->handle($admin, User::factory()->create());

    app(ImpersonateUser::class)->handle(User::factory()->admin()->create(), User::factory()->create());
})->throws(AuthorizationException::class);

test('dejar la impersonación sin una activa falla', function (): void {
    app(StopImpersonating::class)->handle(app(ImpersonateManager::class));
})->throws(AuthorizationException::class);

test('impersonar descarta la confirmación de contraseña del admin', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    $this->actingAs($admin);
    session(['auth.password_confirmed_at' => now()->timestamp]);

    app(ImpersonateUser::class)->handle($admin, $member);

    expect(session()->has('auth.password_confirmed_at'))->toBeFalse();
});
