<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\Tag;
use App\Models\User;

test('publicar un copy-pasta desde /publicar lo deja visible en mis copy-pastas', function (): void {
    $user = User::factory()->create();
    Tag::factory()->create(['name' => 'humor', 'slug' => 'humor']);

    signInInBrowser($user);

    // Title and body sync to the server on blur, so moving focus to the next field (and then to
    // the tag button) is what commits each one — not a fixed wait on a debounced live update.
    visit('/publicar')
        ->fill('title', 'Carta de amor a mi router')
        ->fill('body', 'Querido router, sé que no hablamos mucho pero siempre estás ahí.')
        ->click('#tag-humor')
        ->press(__('public.publish.submit_create'))
        ->assertSee('Carta de amor a mi router');

    visit('/mis-copypastas')->assertSee('Carta de amor a mi router');
});

test('editar un copy-pasta propio actualiza su título', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $user->id, 'title' => 'Título original']);

    signInInBrowser($user);

    visit(route('copypastas.edit', $copypasta))
        ->fill('title', 'Título corregido')
        ->press(__('public.publish.submit_edit'))
        ->assertSee('Título corregido');
});

test('crear una carpeta desde /carpetas la deja disponible', function (): void {
    $user = User::factory()->create();

    signInInBrowser($user);

    visit('/carpetas')
        ->press(__('app.folders.create'))
        ->fill('name', 'Para el grupo')
        ->press(__('public.folders.create'))
        ->assertSee('Para el grupo');
});

test('añadir un copy-pasta a una carpeta y luego quitarlo desde su detalle', function (): void {
    $copypasta = Copypasta::factory()->create();
    $member = User::factory()->create();
    $folder = Folder::factory()->for($member)->create(['name' => 'Recetas de prueba']);

    signInInBrowser($member);

    visit('/')
        ->assertSee($copypasta->title)
        ->click('article button[aria-haspopup="menu"]')
        ->click(__('public.folders.button'))
        ->check('Recetas de prueba')
        ->press('@folders-save-button')
        ->waitForText(__('public.folders.saved'));

    expect($copypasta->folders()->whereKey($folder->getKey())->exists())->toBeTrue();

    visit(route('folders.show', $folder))
        ->assertSee($copypasta->title)
        ->press(__('app.folders.remove'))
        ->waitForText(__('app.folders.empty'));

    expect($copypasta->refresh()->folders()->whereKey($folder->getKey())->exists())->toBeFalse();
});

test('copiar un copy-pasta desde el detalle de una carpeta registra la copia', function (): void {
    $copypasta = Copypasta::factory()->create(['title' => 'Copia desde una carpeta']);
    $member = User::factory()->create();
    $folder = Folder::factory()->for($member)->create();
    $folder->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    signInInBrowser($member);

    $page = visit(route('folders.show', $folder))->assertSee('Copia desde una carpeta');

    $page->script('navigator.clipboard.writeText = async () => {}');

    $page->press('Copiar')
        ->waitForText(__('public.copy.copied'));

    expect($copypasta->refresh()->copies_count)->toBe(1);
});

test('el selector de carpetas se maneja solo con teclado', function (): void {
    $copypasta = Copypasta::factory()->create();
    $member = User::factory()->create();
    $folder = Folder::factory()->for($member)->create(['name' => 'Recetas con teclado']);

    signInInBrowser($member);

    $page = visit('/')->assertSee($copypasta->title);

    $page->keys('article button[aria-haspopup="menu"]', 'Enter')
        ->assertAriaAttribute('article button[aria-haspopup="menu"]', 'expanded', 'true');

    // "Añadir a carpeta" is the first item in the menu, already focused after opening it.
    $page->keys('article [role="menu"]', 'Enter')
        ->waitForText(__('public.folders.title'));

    $page->waitForText('Recetas con teclado')
        ->keys('input[value="'.$folder->getKey().'"]', 'Space');

    $page->keys('[data-test="folders-save-button"]', 'Enter')
        ->waitForText(__('public.folders.saved'));

    expect($copypasta->folders()->whereKey($folder->getKey())->exists())->toBeTrue();
});
