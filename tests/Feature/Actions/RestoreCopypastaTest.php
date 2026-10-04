<?php

declare(strict_types=1);

use App\Actions\RestoreCopypasta;
use App\Enums\ModerationActionType;
use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('un moderador restaura un copy-pasta oculto y registra la acción', function (): void {
    $moderator = User::factory()->moderator()->create();
    $copypasta = Copypasta::factory()->hidden()->create();

    app(RestoreCopypasta::class)->handle($moderator, $copypasta);

    $copypasta->refresh();

    expect($copypasta->isHidden())->toBeFalse()
        ->and($copypasta->hidden_reason)->toBeNull()
        ->and($copypasta->hidden_by_id)->toBeNull();

    $this->assertDatabaseHas('moderation_actions', [
        'actor_id' => $moderator->id,
        'action' => ModerationActionType::Restore->value,
        'subject_type' => Copypasta::class,
        'subject_id' => $copypasta->id,
    ]);
});

test('un usuario normal no puede restaurar copy-pastas', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->hidden()->create();

    app(RestoreCopypasta::class)->handle($user, $copypasta);
})->throws(AuthorizationException::class);
