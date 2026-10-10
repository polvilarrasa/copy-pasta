<?php

declare(strict_types=1);

use App\Livewire\FolderDetail;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Livewire\Livewire;

test('un usuario no abre la vista de una carpeta ajena', function (): void {
    $folder = Folder::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('folders.show', $folder))
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

    $this->actingAs($user)
        ->get(route('folders.show', $folder))
        ->assertOk()
        ->assertSee('Visible')
        ->assertDontSee('Titulo retirado por moderacion')
        ->assertDontSee('Titulo ya borrado')
        ->assertSee(__('app.folders.removed_content'));
});

test('cada copy-pasta de la carpeta ofrece quitarlo de ella', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user)->create();
    $copypasta = Copypasta::factory()->create();
    $folder->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    Livewire::actingAs($user)
        ->test(FolderDetail::class, ['folder' => $folder])
        ->call('removeFromFolder', $copypasta->getKey());

    expect($folder->copypastas()->whereKey($copypasta->getKey())->exists())->toBeFalse();
});

test('quitar un copy-pasta ya borrado de la carpeta funciona', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user)->create();
    $copypasta = Copypasta::factory()->create();
    $folder->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);
    $copypasta->delete();

    Livewire::actingAs($user)
        ->test(FolderDetail::class, ['folder' => $folder])
        ->call('removeFromFolder', $copypasta->getKey());

    expect($folder->copypastas()->whereKey($copypasta->getKey())->exists())->toBeFalse();
});

test('el buscador filtra los copy-pastas de la carpeta', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user)->create();
    $match = Copypasta::factory()->create(['title' => 'Gatos pirata']);
    $other = Copypasta::factory()->create(['title' => 'Perros astronauta']);
    $folder->copypastas()->attach([$match->getKey(), $other->getKey()], ['created_at' => now()]);

    Livewire::actingAs($user)
        ->test(FolderDetail::class, ['folder' => $folder])
        ->set('search', 'gatos')
        ->assertSee('Gatos pirata')
        ->assertDontSee('Perros astronauta');
});
