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

    // The submit button's own label ("Publicar") matches the header's "Publicar" CTA exactly, so a
    // text-based press() is ambiguous between the two — @publish-submit-button targets the form's
    // own button precisely, the same data-test convention used by @folders-save-button.
    // The tag button re-renders with aria-pressed once the server has toggled it, so waiting for that state
    // guarantees the selection is saved before the form is submitted.
    visitInteractive('/publicar')
        ->fill('title', 'Carta de amor a mi router')
        ->fill('body', 'Querido router, sé que no hablamos mucho pero siempre estás ahí.')
        ->click('#tag-humor')
        ->assertAriaAttribute('#tag-humor', 'pressed', 'true')
        ->press('@publish-submit-button')
        // The title is also in the live preview, so wait for the redirect to the detail page before leaving.
        ->assertPathBeginsWith('/c/')
        ->assertSee('Carta de amor a mi router');

    visit('/mis-copypastas')->assertSee('Carta de amor a mi router');
});

test('editar un copy-pasta propio actualiza su título', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $user->id, 'title' => 'Título original']);
    // The form needs between one and five tags to save, and the factory publishes without any.
    $copypasta->tags()->attach(Tag::factory()->create());

    signInInBrowser($user);

    // The new title is already in the live preview, so the saved state is the redirect away from the edit form.
    visitInteractive(route('copypastas.edit', $copypasta))
        ->fill('title', 'Título corregido')
        ->press(__('public.publish.submit_edit'))
        ->assertPathIsNot(parse_url(route('copypastas.edit', $copypasta), PHP_URL_PATH))
        ->assertSee('Título corregido');

    expect($copypasta->refresh()->title)->toBe('Título corregido');
});

test('crear una carpeta desde /carpetas la deja disponible', function (): void {
    $user = User::factory()->create();

    signInInBrowser($user);

    visitInteractive('/carpetas')
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

    visitInteractive('/')
        ->assertSee($copypasta->title)
        ->click('article button[aria-haspopup="menu"]')
        ->click(__('public.folders.button'))
        ->check('Recetas de prueba')
        ->press('@folders-save-button')
        ->waitForText(__('public.folders.saved'));

    expect($copypasta->folders()->whereKey($folder->getKey())->exists())->toBeTrue();

    visitInteractive(route('folders.show', $folder))
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

    $page = visitInteractive(route('folders.show', $folder))->assertSee('Copia desde una carpeta');

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

    $page = visitInteractive('/')->assertSee($copypasta->title);

    $page->keys('article button[aria-haspopup="menu"]', 'Enter')
        ->assertAriaAttribute('article button[aria-haspopup="menu"]', 'expanded', 'true');

    // Alpine moves the focus to the first item on the next tick; Enter sent before that goes nowhere.
    $page->assertScript('() => document.activeElement?.closest(\'[role="menu"]\') !== null')
        ->keys('article [role="menu"]', 'Enter')
        ->waitForText(__('public.folders.title'));

    $page->waitForText('Recetas con teclado')
        ->keys('input[value="'.$folder->getKey().'"]', 'Space');

    $page->keys('[data-test="folders-save-button"]', 'Enter')
        ->waitForText(__('public.folders.saved'));

    expect($copypasta->folders()->whereKey($folder->getKey())->exists())->toBeTrue();
});
