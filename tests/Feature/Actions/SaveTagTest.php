<?php

declare(strict_types=1);

use App\Actions\SaveTag;
use App\Enums\ModerationActionType;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('un moderador crea una etiqueta, genera el slug y registra la acción', function (): void {
    $moderator = User::factory()->moderator()->create();

    $tag = app(SaveTag::class)->handle($moderator, [
        'name' => 'Frases épicas',
        'slug' => null,
        'color' => 't3',
        'is_active' => true,
    ]);

    expect($tag->slug)->toBe('frases-epicas');

    $this->assertDatabaseHas('moderation_actions', [
        'actor_id' => $moderator->id,
        'action' => ModerationActionType::TagCreated->value,
        'subject_type' => Tag::class,
        'subject_id' => (string) $tag->id,
    ]);
});

test('editar una etiqueta registra tag_updated', function (): void {
    $admin = User::factory()->admin()->create();
    $tag = Tag::factory()->create(['name' => 'Viejo', 'slug' => 'viejo']);

    app(SaveTag::class)->handle($admin, [
        'name' => 'Nuevo',
        'slug' => 'nuevo',
        'color' => 't1',
        'is_active' => false,
    ], $tag);

    expect($tag->refresh()->name)->toBe('Nuevo')
        ->and($tag->is_active)->toBeFalse();

    $this->assertDatabaseHas('moderation_actions', [
        'actor_id' => $admin->id,
        'action' => ModerationActionType::TagUpdated->value,
        'subject_id' => (string) $tag->id,
    ]);
});

test('un usuario normal no puede crear etiquetas', function (): void {
    $user = User::factory()->create();

    app(SaveTag::class)->handle($user, [
        'name' => 'Prohibida',
        'slug' => null,
        'color' => 't4',
        'is_active' => true,
    ]);
})->throws(AuthorizationException::class);
