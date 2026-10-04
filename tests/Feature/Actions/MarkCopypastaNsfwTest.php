<?php

declare(strict_types=1);

use App\Actions\MarkCopypastaNsfw;
use App\Enums\ModerationActionType;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('un moderador marca un copy-pasta como NSFW y lo registra', function (): void {
    $moderator = User::factory()->moderator()->create();
    $copypasta = Copypasta::factory()->create(['is_nsfw' => false]);

    app(MarkCopypastaNsfw::class)->handle($moderator, $copypasta, true);

    expect($copypasta->refresh()->is_nsfw)->toBeTrue();

    $this->assertDatabaseHas('moderation_actions', [
        'actor_id' => $moderator->id,
        'action' => ModerationActionType::MarkNsfw->value,
        'subject_id' => $copypasta->id,
    ]);
});

test('quitar el NSFW registra la acción inversa', function (): void {
    $moderator = User::factory()->moderator()->create();
    $copypasta = Copypasta::factory()->nsfw()->create();

    app(MarkCopypastaNsfw::class)->handle($moderator, $copypasta, false);

    expect($copypasta->refresh()->is_nsfw)->toBeFalse();
    $this->assertDatabaseHas('moderation_actions', [
        'action' => ModerationActionType::UnmarkNsfw->value,
        'subject_id' => $copypasta->id,
    ]);
});

test('no registra nada si el valor no cambia', function (): void {
    $moderator = User::factory()->moderator()->create();
    $copypasta = Copypasta::factory()->create(['is_nsfw' => false]);

    app(MarkCopypastaNsfw::class)->handle($moderator, $copypasta, false);

    expect(ModerationAction::query()->count())->toBe(0);
});

test('un usuario normal no puede marcar NSFW', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    app(MarkCopypastaNsfw::class)->handle($user, $copypasta, true);
})->throws(AuthorizationException::class);
