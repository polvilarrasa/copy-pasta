<?php

declare(strict_types=1);

use App\Actions\HideCopypasta;
use App\Enums\ModerationActionType;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

test('un moderador oculta un copy-pasta, guarda el motivo y registra la acción', function (): void {
    $moderator = User::factory()->moderator()->create();
    $copypasta = Copypasta::factory()->publishedDaysAgo(1)->create();

    app(HideCopypasta::class)->handle($moderator, $copypasta, 'Spam repetido');

    $copypasta->refresh();

    expect($copypasta->isHidden())->toBeTrue()
        ->and($copypasta->hidden_reason)->toBe('Spam repetido')
        ->and($copypasta->hidden_by_id)->toBe($moderator->id);

    $this->assertDatabaseHas('moderation_actions', [
        'actor_id' => $moderator->id,
        'action' => ModerationActionType::Hide->value,
        'subject_type' => Copypasta::class,
        'subject_id' => $copypasta->id,
        'reason' => 'Spam repetido',
    ]);
});

test('ocultar exige un motivo no vacío', function (): void {
    $moderator = User::factory()->moderator()->create();
    $copypasta = Copypasta::factory()->create();

    app(HideCopypasta::class)->handle($moderator, $copypasta, '   ');
})->throws(ValidationException::class);

test('un usuario normal no puede ocultar copy-pastas', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    app(HideCopypasta::class)->handle($user, $copypasta, 'Motivo');
})->throws(AuthorizationException::class);

test('ocultar no deja registro si la autorización falla', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    expect(fn () => app(HideCopypasta::class)->handle($user, $copypasta, 'Motivo'))
        ->toThrow(AuthorizationException::class);

    expect(ModerationAction::query()->count())->toBe(0);
});
