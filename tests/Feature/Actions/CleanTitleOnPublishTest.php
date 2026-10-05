<?php

declare(strict_types=1);

use App\Actions\PublishCopypasta;
use App\Actions\UpdateCopypasta;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\TrackedEvent;
use App\Models\User;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => prepareEventPartitions());

function titledPublishData(string $title): array
{
    return [
        'title' => $title,
        'body' => 'Un cuerpo suficientemente largo para publicar.',
        'is_nsfw' => false,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ];
}

test('publicar guarda el título sin caracteres de dirección', function (): void {
    $author = User::factory()->create();

    $copypasta = app(PublishCopypasta::class)->handle($author, titledPublishData("Truco\u{202E}final"));

    expect($copypasta->refresh()->title)->toBe('Trucofinal');
});

test('publicar con un título que queda vacío tras limpiarlo falla la validación', function (): void {
    $author = User::factory()->create();

    app(PublishCopypasta::class)->handle($author, titledPublishData("\u{202E}\u{200B}"));
})->throws(ValidationException::class);

test('el slug de un título sin letras usa el ULID del copy-pasta', function (): void {
    $author = User::factory()->create();

    $copypasta = app(PublishCopypasta::class)->handle($author, titledPublishData('🔥🔥🔥'));

    expect($copypasta->slug)->toBe($copypasta->getKey());
});

test('publicar registra un evento publish con el copy-pasta', function (): void {
    $author = User::factory()->create();

    $copypasta = app(PublishCopypasta::class)->handle($author, titledPublishData('Título normal'));

    expect(TrackedEvent::query()->sole())
        ->type->toBe(EventType::Publish)
        ->user_id->toBe($author->getKey())
        ->copypasta_id->toBe($copypasta->getKey());
});

test('editar limpia el título y registra un evento update', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author)->create(['title' => 'Antiguo']);

    app(UpdateCopypasta::class)->handle($author, $copypasta, [
        ...titledPublishData("Nuevo\u{202E}"),
    ]);

    expect($copypasta->refresh()->title)->toBe('Nuevo')
        ->and(TrackedEvent::query()->sole()->type)->toBe(EventType::Update);
});
