<?php

declare(strict_types=1);

use App\Actions\CreateFolder;
use App\Actions\RenameFolder;
use App\Actions\SetFolderVisibility;
use App\Actions\UpdateFolderDescription;
use App\Enums\EventType;
use App\Livewire\FolderDetail;
use App\Models\Folder;
use App\Models\TrackedEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(fn () => prepareEventPartitions());

test('toda carpeta empieza privada y sin id público', function (): void {
    $folder = app(CreateFolder::class)->handle(User::factory()->create(), 'Nueva');

    expect($folder->refresh())->is_public->toBeFalse()->public_id->toBeNull();
});

test('hacerla pública le da un id público que se conserva al volver a privada y a pública', function (): void {
    $owner = User::factory()->create();
    $folder = Folder::factory()->for($owner)->create();
    $action = app(SetFolderVisibility::class);

    $action->handle($owner, $folder, true);
    $publicId = $folder->refresh()->public_id;

    $action->handle($owner, $folder, false);
    expect($folder->refresh())->is_public->toBeFalse()->public_id->toBe($publicId);

    $action->handle($owner, $folder, true);
    expect($folder->refresh())->is_public->toBeTrue()->public_id->toBe($publicId)
        ->and($publicId)->toMatch('/^[0-9A-Z]{26}$/');
});

test('Favoritos también puede hacerse pública', function (): void {
    $owner = User::factory()->create();
    $favorites = Folder::ensureDefaultFor($owner);

    app(SetFolderVisibility::class)->handle($owner, $favorites, true);

    expect($favorites->refresh()->is_public)->toBeTrue();
});

test('cambiar la visibilidad registra un evento y no repetir el cambio no registra otro', function (): void {
    $owner = User::factory()->create();
    $folder = Folder::factory()->for($owner)->create();

    app(SetFolderVisibility::class)->handle($owner, $folder, true);
    app(SetFolderVisibility::class)->handle($owner, $folder, true);

    $event = TrackedEvent::query()->where('type', EventType::FolderVisibility)->sole();

    expect($event->user_id)->toBe($owner->id)->and($event->context)->toBe(['public' => true]);
});

test('solo el dueño cambia la visibilidad de su carpeta', function (): void {
    $folder = Folder::factory()->create();

    expect(fn () => app(SetFolderVisibility::class)->handle(User::factory()->moderator()->create(), $folder, true))
        ->toThrow(AuthorizationException::class);

    expect($folder->refresh()->is_public)->toBeFalse();
});

test('el nombre y la descripción de una carpeta pasan la limpieza Unicode de los títulos', function (): void {
    $owner = User::factory()->create();

    $folder = app(CreateFolder::class)->handle($owner, "\u{202E}Humor\u{200B} negro");
    expect($folder->name)->toBe('Humor negro');

    app(RenameFolder::class)->handle($owner, $folder, "Chistes\u{2066}\u{FEFF}");
    expect($folder->refresh()->name)->toBe('Chistes');

    app(UpdateFolderDescription::class)->handle($owner, $folder, "  Lo \u{202E}mejor\u{200D} ");
    expect($folder->refresh()->description)->toBe('Lo mejor');
});

test('un nombre que queda vacío tras la limpieza se rechaza y una descripción larga también', function (): void {
    $owner = User::factory()->create();
    $folder = Folder::factory()->for($owner)->create();

    expect(fn () => app(CreateFolder::class)->handle($owner, "\u{200B}\u{202E}"))->toThrow(ValidationException::class)
        ->and(fn () => app(RenameFolder::class)->handle($owner, $folder, "\u{200B}"))->toThrow(ValidationException::class)
        ->and(fn () => app(UpdateFolderDescription::class)->handle($owner, $folder, str_repeat('a', 281)))->toThrow(ValidationException::class);

    app(UpdateFolderDescription::class)->handle($owner, $folder, str_repeat('a', 280));

    expect($folder->refresh()->description)->toHaveLength(280);
});

test('el interruptor de la vista de carpeta la hace pública y privada', function (): void {
    $owner = User::factory()->create();
    $folder = Folder::factory()->for($owner)->create();

    $component = Livewire::actingAs($owner)->test(FolderDetail::class, ['folder' => $folder])
        ->call('togglePublic')
        ->assertSet('isPublic', true);

    expect($folder->refresh()->is_public)->toBeTrue();

    $component->assertSee(route('folders.public', $folder->public_id))
        ->call('togglePublic')
        ->assertSet('isPublic', false);

    expect($folder->refresh()->is_public)->toBeFalse();
});

test('compartir una carpeta privada la hace pública, devuelve su enlace y registra el evento', function (): void {
    $owner = User::factory()->create();
    $folder = Folder::factory()->for($owner)->create();

    $component = Livewire::actingAs($owner)->test(FolderDetail::class, ['folder' => $folder])
        ->call('shareFolder');

    $folder->refresh();

    $component->assertReturned(route('folders.public', $folder->public_id))->assertSet('isPublic', true);

    expect($folder->is_public)->toBeTrue()
        ->and(TrackedEvent::query()->where('type', EventType::FolderShare)->count())->toBe(1);
});

test('nadie más abre la vista de dueño de una carpeta', function (): void {
    $folder = Folder::factory()->public()->create();

    Livewire::actingAs(User::factory()->create())->test(FolderDetail::class, ['folder' => $folder])->assertNotFound();
});
