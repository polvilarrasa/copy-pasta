<?php

declare(strict_types=1);

use App\Actions\ChangeUserRole;
use App\Enums\ModerationActionType;
use App\Enums\Role;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('un admin cambia el rol de un miembro y registra el antes y el después', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    app(ChangeUserRole::class)->handle($admin, $member, Role::Moderator);

    expect($member->refresh()->role)->toBe(Role::Moderator);

    $entry = ModerationAction::query()->where('action', ModerationActionType::ChangeRole)->firstOrFail();

    expect($entry->actor_id)->toBe($admin->id)
        ->and($entry->meta['from'])->toBe('user')
        ->and($entry->meta['to'])->toBe('moderator');
});

test('cambiar a un rol que ya tiene no registra nada', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    app(ChangeUserRole::class)->handle($admin, $member, Role::User);

    expect(ModerationAction::query()->where('action', ModerationActionType::ChangeRole)->exists())->toBeFalse();
});

test('un admin no puede cambiar su propio rol', function (): void {
    $admin = User::factory()->admin()->create();

    app(ChangeUserRole::class)->handle($admin, $admin, Role::User);
})->throws(AuthorizationException::class);

test('un moderador no puede cambiar roles', function (): void {
    app(ChangeUserRole::class)->handle(User::factory()->moderator()->create(), User::factory()->create(), Role::Admin);
})->throws(AuthorizationException::class);
