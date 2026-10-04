<?php

declare(strict_types=1);

use App\Actions\FindDuplicateCopypasta;
use App\Actions\PublishCopypasta;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function publishData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Titulo de prueba',
        'body' => 'Un cuerpo suficientemente largo para publicar.',
        'is_nsfw' => false,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ], $overrides);
}

test('un miembro verificado publica con sus etiquetas activas y fecha de publicación', function (): void {
    $author = User::factory()->create();
    $tag = Tag::factory()->create();

    $copypasta = app(PublishCopypasta::class)->handle($author, publishData(['tag_ids' => [$tag->getKey()]]));

    expect($copypasta->user_id)->toBe($author->id)
        ->and($copypasta->published_at)->not->toBeNull()
        ->and($copypasta->tags->pluck('id')->all())->toBe([$tag->id]);
});

test('no publica sin email verificado', function (): void {
    $author = User::factory()->unverified()->create();

    app(PublishCopypasta::class)->handle($author, publishData());
})->throws(AuthorizationException::class);

test('rechaza más de cinco etiquetas o ninguna', function (): void {
    $author = User::factory()->create();
    $tags = Tag::factory()->count(6)->create()->pluck('id')->all();

    expect(fn () => app(PublishCopypasta::class)->handle($author, publishData(['tag_ids' => $tags])))
        ->toThrow(ValidationException::class);

    expect(fn () => app(PublishCopypasta::class)->handle($author, publishData(['tag_ids' => []])))
        ->toThrow(ValidationException::class);
});

test('rechaza etiquetas desactivadas', function (): void {
    $author = User::factory()->create();
    $inactive = Tag::factory()->inactive()->create();

    expect(fn () => app(PublishCopypasta::class)->handle($author, publishData(['tag_ids' => [$inactive->getKey()]])))
        ->toThrow(ValidationException::class);
});

test('detecta un duplicado exacto ignorando mayúsculas y espacios, sin bloquear la publicación', function (): void {
    $existing = Copypasta::factory()->create(['body' => 'Hola   mundo, esto es un texto']);

    $duplicate = app(FindDuplicateCopypasta::class)->handle('  HOLA mundo, esto es un texto ');

    expect($duplicate?->getKey())->toBe($existing->getKey());

    $copypasta = app(PublishCopypasta::class)->handle(User::factory()->create(), publishData([
        'body' => 'HOLA mundo, esto es un texto',
    ]));

    expect($copypasta->exists)->toBeTrue();
});

test('el aviso de duplicado ignora los copy-pastas ocultos', function (): void {
    Copypasta::factory()->hidden()->create(['body' => 'Texto que ya fue retirado']);

    expect(app(FindDuplicateCopypasta::class)->handle('Texto que ya fue retirado'))->toBeNull();
});
