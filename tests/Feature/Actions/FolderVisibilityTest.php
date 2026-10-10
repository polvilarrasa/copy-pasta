<?php

declare(strict_types=1);

use App\Actions\CreateFolder;
use App\Actions\MakeFolderPrivate;
use App\Actions\RenameFolder;
use App\Actions\SetFolderVisibility;
use App\Actions\ShareFolder;
use App\Actions\UnlockFolder;
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

function lockedFolder(?User $owner = null): Folder
{
    $folder = Folder::factory()->public()->for($owner ?? User::factory()->create())->create(['name' => 'Carpeta moderada']);

    return app(MakeFolderPrivate::class)->handle(User::factory()->moderator()->create(), $folder, 'Contenido que incumple las normas');
}

test('hacerla privada desde moderación la bloquea y el dueño no puede volver a publicarla con la Action', function (): void {
    $folder = lockedFolder();

    expect($folder->refresh())->is_public->toBeFalse()->public_locked_at->not->toBeNull()
        ->and(fn () => app(SetFolderVisibility::class)->handle($folder->user, $folder, true))->toThrow(AuthorizationException::class)
        ->and(fn () => app(ShareFolder::class)->handle($folder->user, $folder))->toThrow(AuthorizationException::class);

    expect($folder->refresh()->is_public)->toBeFalse();
});

test('el bloqueo se comprueba sobre la fila actual aunque el modelo en memoria esté desfasado', function (): void {
    $owner = User::factory()->create();
    $folder = Folder::factory()->public()->for($owner)->create();
    $stale = Folder::query()->find($folder->id);

    app(MakeFolderPrivate::class)->handle(User::factory()->moderator()->create(), $folder, 'Motivo');

    expect(fn () => app(SetFolderVisibility::class)->handle($owner, $stale, true))->toThrow(AuthorizationException::class);
});

test('el dueño sigue pudiendo hacer privada una carpeta bloqueada y no registra nada', function (): void {
    $folder = lockedFolder();

    app(SetFolderVisibility::class)->handle($folder->user, $folder, false);

    expect($folder->refresh()->is_public)->toBeFalse()
        ->and(TrackedEvent::query()->where('type', EventType::FolderVisibility)->count())->toBe(0);
});

test('en la interfaz el interruptor sale deshabilitado con el aviso y el motivo, sin botón de compartir', function (): void {
    $folder = lockedFolder();

    Livewire::actingAs($folder->user)->test(FolderDetail::class, ['folder' => $folder])
        ->assertSee('El equipo de moderación ha hecho privada esta carpeta')
        ->assertSee('Motivo: Contenido que incumple las normas')
        ->assertSeeHtml('data-test="folder-public-switch"')
        ->assertDontSeeHtml('data-test="folder-share-button"')
        ->assertSeeHtml('disabled');
});

test('el dueño no la publica por la interfaz: ni con el interruptor ni compartiéndola', function (): void {
    $folder = lockedFolder();

    Livewire::actingAs($folder->user)->test(FolderDetail::class, ['folder' => $folder])
        ->call('togglePublic')
        ->assertForbidden();

    Livewire::actingAs($folder->user)->test(FolderDetail::class, ['folder' => $folder])
        ->call('shareFolder')
        ->assertForbidden();

    expect($folder->refresh()->is_public)->toBeFalse()->and($folder->public_id)->not->toBeNull();
    $this->get(route('folders.public', $folder->public_id))->assertNotFound();
});

test('desbloquearla no la hace pública y el dueño puede volver a decidir', function (): void {
    $folder = lockedFolder();

    app(UnlockFolder::class)->handle(User::factory()->moderator()->create(), $folder, 'Revisada');

    expect($folder->refresh())->is_public->toBeFalse()->public_locked_at->toBeNull()->public_lock_reason->toBeNull();

    Livewire::actingAs($folder->user)->test(FolderDetail::class, ['folder' => $folder])
        ->assertDontSee('El equipo de moderación ha hecho privada esta carpeta')
        ->call('togglePublic')
        ->assertSet('isPublic', true);
});

test('un usuario normal no desbloquea una carpeta', function (): void {
    $folder = lockedFolder();

    expect(fn () => app(UnlockFolder::class)->handle($folder->user, $folder, 'Quiero publicarla'))->toThrow(AuthorizationException::class);
    expect($folder->refresh()->isPublicLocked())->toBeTrue();
});
