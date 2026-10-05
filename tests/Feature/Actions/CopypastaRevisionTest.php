<?php

declare(strict_types=1);

use App\Actions\PublishCopypasta;
use App\Actions\SaveCopypastaRevision;
use App\Actions\UpdateCopypasta;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;

test('publicar guarda la primera versión del copy-pasta', function (): void {
    $author = User::factory()->established()->create();

    $copypasta = app(PublishCopypasta::class)->handle($author, [
        'title' => 'Titulo original',
        'body' => 'Cuerpo original suficientemente largo',
        'is_nsfw' => false,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ]);

    expect($copypasta->revisions)->toHaveCount(1)
        ->and($copypasta->revisions->first()->title)->toBe('Titulo original');
});

test('editar el título o el cuerpo guarda una versión nueva y conserva la anterior', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $author->getKey(), 'title' => 'Antes', 'body' => 'Cuerpo antes']);
    app(SaveCopypastaRevision::class)->handle($copypasta);

    app(UpdateCopypasta::class)->handle($author, $copypasta, [
        'title' => 'Despues',
        'body' => 'Cuerpo despues',
        'is_nsfw' => false,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ]);

    expect($copypasta->refresh()->revisions->pluck('title')->all())->toBe(['Antes', 'Despues']);
});

test('editar solo las etiquetas o el aviso +18 no crea una versión', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $author->getKey(), 'title' => 'Igual', 'body' => 'Cuerpo igual']);
    app(SaveCopypastaRevision::class)->handle($copypasta);

    app(UpdateCopypasta::class)->handle($author, $copypasta, [
        'title' => 'Igual',
        'body' => 'Cuerpo igual',
        'is_nsfw' => true,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ]);

    expect($copypasta->refresh()->revisions)->toHaveCount(1);
});
