<?php

declare(strict_types=1);

use App\Actions\UnbanUser;
use App\Enums\ModerationActionType;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('un admin desbanea a un miembro y registra la acción', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->banned()->create();

    app(UnbanUser::class)->handle($admin, $member);

    expect($member->refresh()->isBanned())->toBeFalse()
        ->and($member->ban_reason)->toBeNull();

    $this->assertDatabaseHas('moderation_actions', [
        'actor_id' => $admin->id,
        'action' => ModerationActionType::Unban->value,
        'subject_id' => $member->id,
    ]);
});

test('un moderador no puede desbanear', function (): void {
    app(UnbanUser::class)->handle(User::factory()->moderator()->create(), User::factory()->banned()->create());
})->throws(AuthorizationException::class);
