<?php

declare(strict_types=1);

use App\Actions\MakeFolderPrivate;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\TrackedEvent;
use App\Models\User;

beforeEach(fn () => prepareEventPartitions());

/**
 * True when the share-image canvas is 1080 × 1080, has the style's background in the corner and has something drawn
 * over it.
 */
function shareCanvasIsDrawnScript(string $background): string
{
    return <<<JS
        () => {
            const canvas = document.querySelector('canvas');
            if (! canvas || canvas.width !== 1080 || canvas.height !== 1080) { return false; }
            const data = canvas.getContext('2d').getImageData(0, 0, 1080, 1080).data;
            const hex = (offset) => [data[offset], data[offset + 1], data[offset + 2]].map((value) => value.toString(16).padStart(2, '0')).join('');
            if (hex(0) !== '{$background}') { return false; }
            for (let offset = 0; offset < data.length; offset += 4 * 97) {
                if (hex(offset) !== '{$background}') { return true; }
            }
            return false;
        }
        JS;
}

test('hacer pública una carpeta, abrirla sin sesión y ver solo los copy-pastas visibles', function (): void {
    $owner = User::factory()->create();
    $folder = Folder::factory()->for($owner)->create(['name' => 'Carpeta de joyas', 'description' => 'Lo mejor del foro']);
    $visible = Copypasta::factory()->create(['title' => 'Visible para todos']);
    $hidden = Copypasta::factory()->hidden()->create(['title' => 'Titulo retirado por moderacion']);
    $folder->copypastas()->attach([$visible->getKey(), $hidden->getKey()], ['created_at' => now()]);

    signInInBrowser($owner);

    visitInteractive(route('folders.show', $folder))
        ->assertDontSee(__('public.public_folder.public_url'))
        ->click(__('public.public_folder.public_label'))
        ->assertPresent('@folder-public-url');

    $folder->refresh();

    expect($folder->is_public)->toBeTrue();

    $url = route('folders.public', $folder->public_id);

    // The sign-out button lives in the user menu, which is closed: submitting its form is what matters here.
    visitInteractive($url)
        ->script("document.querySelector('[data-test=logout-button]').click()");

    visit('/login')->assertPathIs('/login');

    visit($url)
        ->assertSee('Carpeta de joyas')
        ->assertSee('Lo mejor del foro')
        ->assertSee($owner->username)
        ->assertSee('Visible para todos')
        ->assertDontSee('Titulo retirado por moderacion')
        ->assertDontSee(__('app.folders.removed_content'));

    $folder->forceFill(['is_public' => false])->save();

    visit($url)->assertDontSee('Carpeta de joyas');
});

test('compartir una carpeta privada pide confirmación y la hace pública', function (): void {
    $owner = User::factory()->create();
    $folder = Folder::factory()->for($owner)->create(['name' => 'Carpeta privada']);

    signInInBrowser($owner);

    visitInteractive(route('folders.show', $folder))
        ->click('@folder-share-button')
        ->assertSee(__('public.public_folder.share_title'))
        ->click('@folder-share-confirm')
        ->assertPresent('@folder-public-url');

    expect($folder->refresh()->is_public)->toBeTrue()
        ->and(TrackedEvent::query()->where('type', EventType::FolderShare)->count())->toBe(1);
});

test('compartir como imagen dibuja el lienzo, cambia de estilo y registra el evento share con method=image al descargar', function (): void {
    $copypasta = Copypasta::factory()->create(['title' => 'Aviso sobre el táper', 'body' => "Hay un táper en la nevera.\nSi es tuyo, reclámalo 😅"]);

    $page = visitInteractive(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->click('@share-image-toggle')
        ->assertScript(shareCanvasIsDrawnScript('0e0d1a'));

    $page->click('@share-image-style-light')
        ->assertScript(shareCanvasIsDrawnScript('f3f2ff'))
        ->click('@share-image-style-lime')
        ->assertScript(shareCanvasIsDrawnScript('c6f432'))
        ->click('@share-image-download');

    eventually(function (): void {
        $event = TrackedEvent::query()->where('type', EventType::Share)->first();

        expect($event)->not->toBeNull()
            ->and($event->context)->toMatchArray(['method' => 'image'])
            ->and($event->copypasta_id)->toBe(Copypasta::query()->first()->getKey());
    });
});

test('compartir como imagen un NSFW pide confirmación antes de dibujar nada', function (): void {
    $copypasta = Copypasta::factory()->nsfw()->create();

    visitInteractive(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->click('@share-image-toggle')
        ->assertSee(__('public.share_image.nsfw_title'))
        ->assertScript("() => document.querySelector('canvas').width !== 1080")
        ->click('@share-image-confirm')
        ->assertScript(shareCanvasIsDrawnScript('0e0d1a'));
});

test('una carpeta que moderación hizo privada muestra el interruptor deshabilitado con el motivo y sin compartir', function (): void {
    $owner = User::factory()->create();
    $folder = Folder::factory()->public()->for($owner)->create(['name' => 'Carpeta moderada']);
    app(MakeFolderPrivate::class)->handle(User::factory()->moderator()->create(), $folder, 'Contenido que incumple las normas');

    signInInBrowser($owner);

    visitInteractive(route('folders.show', $folder))
        ->assertSee('El equipo de moderación ha hecho privada esta carpeta')
        ->assertSee('Contenido que incumple las normas')
        ->assertScript("() => document.querySelector('[data-test=folder-public-switch]').disabled === true")
        ->assertNotPresent('@folder-share-button');

    expect($folder->refresh()->is_public)->toBeFalse();
});
