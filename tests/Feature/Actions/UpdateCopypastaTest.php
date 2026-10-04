<?php

declare(strict_types=1);

use App\Actions\UpdateCopypasta;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('el autor edita y queda marcado como editado', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $author->id, 'edited_at' => null]);
    $tag = Tag::factory()->create();

    app(UpdateCopypasta::class)->handle($author, $copypasta, [
        'title' => 'Titulo nuevo',
        'body' => 'Cuerpo nuevo suficientemente largo',
        'is_nsfw' => true,
        'tag_ids' => [$tag->getKey()],
    ]);

    $copypasta->refresh();

    expect($copypasta->title)->toBe('Titulo nuevo')
        ->and($copypasta->slug)->toBe('titulo-nuevo')
        ->and($copypasta->is_nsfw)->toBeTrue()
        ->and($copypasta->edited_at)->not->toBeNull()
        ->and($copypasta->tags->pluck('id')->all())->toBe([$tag->id]);
});

test('editar un copy-pasta oculto lo deja oculto', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->hidden('Spam')->create(['user_id' => $author->id]);

    app(UpdateCopypasta::class)->handle($author, $copypasta, [
        'title' => 'Titulo corregido',
        'body' => 'Cuerpo corregido suficientemente largo',
        'is_nsfw' => false,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ]);

    expect($copypasta->refresh()->isHidden())->toBeTrue()
        ->and($copypasta->hidden_reason)->toBe('Spam');
});

test('al editar conserva las etiquetas que se desactivaron después de publicar', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $author->id]);
    $retired = Tag::factory()->inactive()->create();
    $active = Tag::factory()->create();
    $copypasta->tags()->attach($retired->getKey());

    app(UpdateCopypasta::class)->handle($author, $copypasta, [
        'title' => 'Mismo titulo',
        'body' => 'Mismo cuerpo suficientemente largo',
        'is_nsfw' => false,
        'tag_ids' => [$active->getKey()],
    ]);

    expect($copypasta->tags()->pluck('tags.id')->sort()->values()->all())
        ->toBe([$retired->id, $active->id]);
});

test('otro usuario no puede editar el copy-pasta', function (): void {
    $copypasta = Copypasta::factory()->create();

    app(UpdateCopypasta::class)->handle(User::factory()->create(), $copypasta, [
        'title' => 'Intento',
        'body' => 'Intento de edición suficientemente largo',
        'is_nsfw' => false,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ]);
})->throws(AuthorizationException::class);
