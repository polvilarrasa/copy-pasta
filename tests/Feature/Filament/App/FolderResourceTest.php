<?php

declare(strict_types=1);

use App\Filament\App\Resources\Folders\FolderResource;
use App\Filament\App\Resources\Folders\Pages\ListFolders;
use App\Filament\App\Resources\Folders\Pages\ViewFolder;
use App\Filament\App\Resources\Folders\RelationManagers\CopypastasRelationManager;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('app')));

test('la lista muestra solo las carpetas del usuario', function (): void {
    $user = User::factory()->create();
    $own = Folder::factory()->for($user)->create();
    $foreign = Folder::factory()->create();

    Livewire::actingAs($user)
        ->test(ListFolders::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

test('Favoritos no ofrece renombrar ni borrar', function (): void {
    $user = User::factory()->create();
    $favorites = Folder::factory()->default()->for($user)->create();
    $custom = Folder::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(ListFolders::class)
        ->assertTableActionHidden('edit', $favorites)
        ->assertTableActionHidden('delete', $favorites)
        ->assertTableActionVisible('edit', $custom)
        ->assertTableActionVisible('delete', $custom);
});

test('el usuario crea una carpeta desde el modal', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ListFolders::class)
        ->callAction(TestAction::make('create')->table(), data: ['name' => 'Recetas'])
        ->assertHasNoActionErrors();

    expect(Folder::query()->where('user_id', $user->id)->where('name', 'Recetas')->exists())->toBeTrue();
});

test('el modal de creación rechaza un nombre repetido', function (): void {
    $user = User::factory()->create();
    Folder::factory()->for($user)->create(['name' => 'Recetas']);

    Livewire::actingAs($user)
        ->test(ListFolders::class)
        ->callAction(TestAction::make('create')->table(), data: ['name' => 'Recetas'])
        ->assertHasActionErrors(['name']);
});

test('el usuario renombra una carpeta desde el modal', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user)->create(['name' => 'Vieja']);

    Livewire::actingAs($user)
        ->test(ListFolders::class)
        ->callAction(TestAction::make('edit')->table($folder), data: ['name' => 'Nueva'])
        ->assertHasNoActionErrors();

    expect($folder->refresh()->name)->toBe('Nueva');
});

test('el usuario borra una carpeta y sus copy-pastas siguen existiendo', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user)->create();
    $copypasta = Copypasta::factory()->create();
    $folder->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    Livewire::actingAs($user)
        ->test(ListFolders::class)
        ->callAction(TestAction::make('delete')->table($folder));

    expect(Folder::query()->whereKey($folder->getKey())->exists())->toBeFalse()
        ->and(Copypasta::query()->whereKey($copypasta->getKey())->exists())->toBeTrue();
});

test('reordenar guarda el nuevo orden de las carpetas', function (): void {
    $user = User::factory()->create();
    $first = Folder::factory()->for($user)->create(['position' => 1]);
    $second = Folder::factory()->for($user)->create(['position' => 2]);

    Livewire::actingAs($user)
        ->test(ListFolders::class)
        ->call('reorderTable', [$second->getKey(), $first->getKey()]);

    expect(Folder::query()->whereKey([$first->getKey(), $second->getKey()])->orderBy('position')->pluck('id')->all())
        ->toBe([$second->getKey(), $first->getKey()]);
});

test('reordenar no mueve carpetas ajenas', function (): void {
    $user = User::factory()->create();
    $own = Folder::factory()->for($user)->create(['position' => 1]);
    $foreign = Folder::factory()->create(['position' => 7]);

    Livewire::actingAs($user)
        ->test(ListFolders::class)
        ->call('reorderTable', [$foreign->getKey(), $own->getKey()]);

    expect($foreign->refresh()->position)->toBe(7);
});

test('un usuario no abre la vista de una carpeta ajena', function (): void {
    $folder = Folder::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(FolderResource::getUrl('view', ['record' => $folder]))
        ->assertNotFound();
});

test('la vista de carpeta muestra los copy-pastas con placeholder para ocultos y borrados', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user)->create();
    $visible = Copypasta::factory()->create(['title' => 'Visible']);
    $hidden = Copypasta::factory()->hidden()->create(['title' => 'Titulo retirado por moderacion']);
    $deleted = Copypasta::factory()->create(['title' => 'Titulo ya borrado']);
    $deleted->delete();
    $folder->copypastas()->attach([
        $visible->getKey(), $hidden->getKey(), $deleted->getKey(),
    ], ['created_at' => now()]);

    Livewire::actingAs($user)
        ->test(CopypastasRelationManager::class, ['ownerRecord' => $folder, 'pageClass' => ViewFolder::class])
        ->assertCanSeeTableRecords([$visible, $hidden, $deleted])
        ->assertSee('Visible')
        ->assertDontSee('Titulo retirado por moderacion')
        ->assertDontSee('Titulo ya borrado')
        ->assertSee('Contenido retirado');
});

test('cada copy-pasta de la carpeta ofrece quitarlo de ella', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user)->create();
    $copypasta = Copypasta::factory()->create();
    $folder->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    Livewire::actingAs($user)
        ->test(CopypastasRelationManager::class, ['ownerRecord' => $folder, 'pageClass' => ViewFolder::class])
        ->assertTableActionExists('removeFromFolder', null, $copypasta);
});
