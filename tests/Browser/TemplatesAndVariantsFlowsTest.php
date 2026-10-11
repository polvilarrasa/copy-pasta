<?php

declare(strict_types=1);

use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\TrackedEvent;
use App\Models\User;

beforeEach(fn () => prepareEventPartitions());

/**
 * Headless Chromium denies clipboard writes without a granted permission, so the text is kept in the page instead, and
 * the copy requests are recorded to see exactly what the page sends.
 */
function captureCopyScript(): string
{
    return <<<'JS'
        window.__copied = null;
        window.__copyRequests = [];
        navigator.clipboard.writeText = async (text) => { window.__copied = text; };
        const originalFetch = window.fetch;
        window.fetch = (url, options) => {
            if (String(url).endsWith('/copia')) { window.__copyRequests.push(String(options?.body ?? '')); }
            return originalFetch(url, options);
        };
        JS;
}

test('copiar una plantilla rellenando dos variables copia el texto sustituido y no envía los valores', function (): void {
    $template = Copypasta::factory()->template()->create([
        'title' => 'Aviso con huecos',
        'body' => 'Hola {{nombre}}, vives en {{ ciudad }}. Adiós {{Nombre}}.',
    ]);

    $page = visitInteractive(route('copypastas.show', [$template, $template->slug]))
        ->assertSee(__('ui.card.template'));

    $page->script(captureCopyScript());

    $page->click('@card-copy')
        ->assertSee(__('public.template.copy_title'))
        ->assertScript("() => document.querySelectorAll('[data-test^=template-field-]').length === 2")
        ->fill('@template-field-nombre', 'Ana')
        ->fill('@template-field-ciudad', 'Valencia')
        ->assertScript("() => document.querySelector('[data-test=template-preview]').textContent === 'Hola Ana, vives en Valencia. Adiós Ana.'")
        ->click('@template-copy-button')
        ->waitForText(__('public.copy.copied'))
        ->assertScript("() => window.__copied === 'Hola Ana, vives en Valencia. Adiós Ana.'");

    eventually(function () use ($template): void {
        $event = TrackedEvent::query()->where('type', EventType::Copy)->sole();

        expect($template->refresh()->copies_count)->toBe(1)
            ->and($event->context)->toBe(['template' => true]);
    });

    $page->assertScript("() => window.__copyRequests.length === 1 && window.__copyRequests[0] === '{\"template\":true}'");
    $page->assertScript("() => ! window.__copyRequests.join('').includes('Ana') && ! window.__copyRequests.join('').includes('Valencia')");
});

test('un campo vacío deja la variable tal como estaba escrita', function (): void {
    $template = Copypasta::factory()->template()->create(['body' => 'Hola {{nombre}}, vives en {{ ciudad }}.']);

    $page = visitInteractive(route('copypastas.show', [$template, $template->slug]));
    $page->script(captureCopyScript());

    $page->click('@card-copy')
        ->fill('@template-field-nombre', 'Ana')
        ->click('@template-copy-button')
        ->waitForText(__('public.copy.copied'))
        ->assertScript("() => window.__copied === 'Hola Ana, vives en {{ ciudad }}.'");
});

test('crear una variante desde el detalle la deja en la lista de variantes del original', function (): void {
    $parent = Copypasta::factory()->create(['title' => 'Aviso del táper original', 'body' => 'El texto original del aviso']);
    $parent->tags()->attach(Tag::factory()->create());
    $member = User::factory()->create(['username' => 'remezclador']);

    signInInBrowser($member);

    visitInteractive(route('copypastas.show', [$parent, $parent->slug]))
        ->click('@create-variant')
        ->assertPathIs('/c/'.$parent->getKey().'/variante')
        ->assertValue('title', 'Aviso del táper original')
        ->assertValue('body', 'El texto original del aviso')
        ->fill('body', 'El texto del aviso, pero con mi toque personal')
        ->press('@publish-submit-button')
        ->assertPathIsNot('/c/'.$parent->getKey().'/variante')
        ->assertSee(__('public.variants.variant_of'));

    $variant = Copypasta::query()->whereNotNull('parent_id')->sole();

    expect($variant->parent_id)->toBe($parent->getKey())->and($variant->user_id)->toBe($member->getKey());

    visitInteractive(route('copypastas.show', [$parent, $parent->slug]))
        ->assertSee(__('public.variants.heading'))
        ->assertSee('@remezclador')
        ->click('#variants-tab-v1')
        ->assertSee('El texto del aviso, pero con mi toque personal');
});

test('publicar una plantilla muestra las variables detectadas y deja el distintivo en el detalle', function (): void {
    $user = User::factory()->create();
    Tag::factory()->create(['name' => 'humor', 'slug' => 'humor']);

    signInInBrowser($user);

    visitInteractive('/publicar')
        ->fill('title', 'Carta con huecos para rellenar')
        ->fill('body', 'Querido {{destinatario}}, te escribo por {{motivo}}.')
        ->click('#tag-humor')
        ->assertAriaAttribute('#tag-humor', 'pressed', 'true')
        ->click(__('app.fields.is_template'))
        ->assertSee('{{destinatario}}')
        ->assertSee('{{motivo}}')
        ->press('@publish-submit-button')
        ->assertPathBeginsWith('/c/')
        ->assertSee(__('ui.card.template'));

    expect(Copypasta::query()->sole()->is_template)->toBeTrue();
});
