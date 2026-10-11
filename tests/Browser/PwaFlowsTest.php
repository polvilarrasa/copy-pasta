<?php

declare(strict_types=1);

use App\Enums\EventType;
use App\Livewire\Feed;
use App\Models\Copypasta;
use App\Models\TrackedEvent;
use App\Models\User;
use Pest\Browser\Playwright\Client;

beforeEach(fn () => prepareEventPartitions());

/**
 * Cuts or restores the browser's network (Playwright's `context.setOffline`). The plugin does not expose it, so this
 * sends the protocol message through the plugin's client, which is marked internal: if a plugin update breaks it, the
 * offline checks fall back to scripts/check-pwa.mjs, which does the same against any address.
 */
function setBrowserOffline(mixed $page, bool $offline): void
{
    $context = $page->page()->context();
    $guid = (new ReflectionProperty($context, 'guid'))->getValue($context);

    iterator_to_array(Client::instance()->execute($guid, 'setOffline', ['offline' => $offline]));
}

/**
 * Waits until the service worker is active and controls the page.
 */
function waitForServiceWorker(mixed $page): void
{
    $page->assertScript(<<<'JS'
        async () => {
            await navigator.serviceWorker.ready;

            if (! navigator.serviceWorker.controller) {
                await new Promise((resolve) => navigator.serviceWorker.addEventListener('controllerchange', resolve, { once: true }));
            }

            return true;
        }
        JS);
}

/**
 * Makes the requests whose address contains $fragment answer with $status, or take $delay milliseconds when no
 * status is given. For what the browser cannot be told to do any other way, like an expired session.
 */
function interceptRequests(mixed $page, string $fragment, ?int $status = null, int $delay = 0): void
{
    $status ??= 0;

    $page->script(<<<JS
        const original = window.fetch.bind(window);

        window.fetch = (input, init) => String(input?.url ?? input).includes('{$fragment}')
            ? new Promise((resolve) => setTimeout(resolve, {$delay})).then(() => {$status} ? new Response('', { status: {$status} }) : original(input, init))
            : original(input, init);
        JS);
}

// Pages, history and the cache -------------------------------------------------------------------------------------

test('en el feed, tras "Cargar más", abrir un detalle y volver atrás conserva lo cargado y sigue funcionando', function (): void {
    Copypasta::factory()->count((Feed::PER_PAGE * 2) + 5)->create();
    $member = User::factory()->create();

    signInInBrowser($member);

    $articles = 'document.querySelectorAll("article").length';
    $page = visitInteractive('/nuevos')
        ->assertScript($articles, Feed::PER_PAGE)
        ->press(__('public.feed.load_more'))
        ->assertScript($articles, Feed::PER_PAGE * 2);

    // Resting the pointer on a title must not fetch the detail page: a prefetch would count as a visit.
    $page->hover('article >> nth=0 >> h3 a')->wait(0.6)
        ->assertScript('performance.getEntriesByType("resource").map((entry) => entry.initiatorType + " " + new URL(entry.name).pathname).filter((entry) => entry.includes(" /c/")).join(",")', '');

    // By script: Playwright's own click waits for a navigation that Livewire's wire:navigate never reports.
    $page->script('document.querySelector("article h3 a").click()');

    $page->assertPathBeginsWith('/c/')
        ->back()
        ->assertPathIs('/nuevos')
        ->assertScript($articles, Feed::PER_PAGE * 2);

    // The restored page has to keep working: vote a card, then load the rest.
    $vote = 'article button[aria-label="'.__('ui.card.vote_up').'"]';

    $page->script('document.querySelector(\''.$vote.'\').click()');
    $page->assertScript('document.querySelector(\''.$vote.'\').getAttribute("aria-pressed") === "true"')
        ->press(__('public.feed.load_more'))
        ->assertScript($articles, (Feed::PER_PAGE * 2) + 5)
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();

    expect(Copypasta::query()->where('score', 1)->count())->toBe(1);
});

test('cerrar sesión y volver atrás no muestra contenido de la sesión anterior', function (): void {
    $member = User::factory()->create();
    Copypasta::factory()->for($member, 'user')->create(['title' => 'Título solo visible con sesión']);

    signInInBrowser($member);

    $page = visitInteractive('/mis-copypastas')->assertSee('Título solo visible con sesión');

    // The user menu is closed, so the button is pressed by script (as the other browser tests that sign out do).
    $page->script('document.querySelector("[data-test=logout-button]").click()');

    $page->waitForText(__('public.layout.login'))
        ->back()
        ->assertDontSee('Título solo visible con sesión')
        ->assertPathIs('/login');
});

// Service worker ---------------------------------------------------------------------------------------------------

test('el service worker se registra y, sin conexión, una navegación muestra la página offline', function (): void {
    Copypasta::factory()->create();

    $page = visitInteractive('/nuevos');

    waitForServiceWorker($page);
    setBrowserOffline($page, true);

    try {
        $page->navigate('/mis-copypastas')
            ->assertSee(__('pwa.offline.title'))
            ->assertSee(__('pwa.offline.retry'));
    } finally {
        setBrowserOffline($page, false);
    }
});

test('la página offline se ve sin la red y su botón Reintentar vuelve a pedir la página', function (): void {
    $page = visitInteractive('/nuevos');

    waitForServiceWorker($page);
    setBrowserOffline($page, true);

    $page->navigate('/normas')->assertSee(__('pwa.offline.title'));

    setBrowserOffline($page, false);

    $page->click('@offline-retry')->assertPathIs('/normas')->assertDontSee(__('pwa.offline.title'));
});

test('el service worker no guarda páginas, solo la página offline y los assets del build', function (): void {
    $member = User::factory()->create();

    signInInBrowser($member);

    $page = visitInteractive('/mis-copypastas');

    waitForServiceWorker($page);
    $page->navigate('/carpetas')->assertPathIs('/carpetas')->assertScript(interactivePageScript());

    $page->assertScript(<<<'JS'
        async () => {
            const paths = [];

            for (const name of await caches.keys()) {
                for (const request of await (await caches.open(name)).keys()) {
                    paths.push(new URL(request.url).pathname);
                }
            }

            return paths.includes('/sin-conexion') && paths.every((path) => path === '/sin-conexion' || path === '/favicon.svg' || path.startsWith('/build/'));
        }
        JS);
});

test('al activarse un worker nuevo se borran las cachés antiguas de la app y solo esas', function (): void {
    $page = visitInteractive('/nuevos');

    waitForServiceWorker($page);

    $page->script(<<<'JS'
        (async () => {
            await caches.open('copypastas-antigua');
            await caches.open('de-otra-cosa');

            // A different script address under the same scope replaces the active worker, as a new build would.
            await navigator.serviceWorker.register('/sw.js?actualizado', { scope: '/' });
        })()
        JS);

    $page->assertScript(<<<'JS'
        async () => {
            const registration = await navigator.serviceWorker.ready;
            const names = await caches.keys();

            return registration.active?.scriptURL.endsWith('?actualizado')
                && registration.active?.state === 'activated'
                && names.includes('de-otra-cosa')
                && ! names.includes('copypastas-antigua')
                && names.filter((name) => name.startsWith('copypastas-')).length === 1;
        }
        JS);
});

// Install ----------------------------------------------------------------------------------------------------------

test('"Instalar app" solo aparece en el menú cuando el navegador ofrece instalar y al instalar se registra app_install', function (): void {
    $member = User::factory()->create();

    signInInBrowser($member);

    $page = visitInteractive('/nuevos');

    $page->assertScript('! document.querySelector("[data-test=install-app]").checking && getComputedStyle(document.querySelector("[data-test=install-app]").parentElement).display === "none"');

    $page->script(<<<'JS'
        const offer = new Event('beforeinstallprompt', { cancelable: true });
        offer.prompt = async () => {};
        window.dispatchEvent(offer);
        JS);

    $page->assertScript('getComputedStyle(document.querySelector("[data-test=install-app]").parentElement).display !== "none"');

    $page->script('window.dispatchEvent(new Event("appinstalled"))');

    $page->assertScript('getComputedStyle(document.querySelector("[data-test=install-app]").parentElement).display === "none"');

    eventually(function () use ($member): void {
        expect(TrackedEvent::query()->where('type', EventType::AppInstall)->where('user_id', $member->id)->count())->toBe(1);
    });
});

// Share target -----------------------------------------------------------------------------------------------------

test('compartir texto a /publicar sin sesión lleva al login y después al formulario con los datos', function (): void {
    $member = User::factory()->create();

    visit('/publicar?'.http_build_query(['title' => 'Título que llega de otra app', 'text' => 'Texto compartido desde el menú de Android']))
        ->assertPathIs('/login')
        ->fill('email', $member->email)
        ->fill('password', 'password')
        ->press('@login-button')
        ->assertPathIs('/publicar')
        ->assertScript('() => !!window.Livewire')
        ->assertValue('#field-title', 'Título que llega de otra app')
        ->assertValue('textarea[name="body"]', 'Texto compartido desde el menú de Android');
});

// Failures of the network -----------------------------------------------------------------------------------------

test('sin red, votar revierte el voto y muestra el toast de error', function (): void {
    Copypasta::factory()->create();
    signInInBrowser(User::factory()->create());

    $page = visitInteractive('/nuevos');
    $vote = 'button[aria-label="'.__('ui.card.vote_up').'"]';

    setBrowserOffline($page, true);

    $page->click($vote)
        ->waitForText(__('public.copy.action_failed'))
        ->assertAttribute($vote, 'aria-pressed', 'false');

    setBrowserOffline($page, false);
});

test('sin red, guardar en favoritos revierte la estrella y muestra el toast de error', function (): void {
    Copypasta::factory()->create();
    signInInBrowser(User::factory()->create());

    $page = visitInteractive('/nuevos');

    setBrowserOffline($page, true);

    $page->click(__('ui.card.save'))
        ->waitForText(__('public.copy.action_failed'))
        ->assertScript('document.querySelector("article [aria-pressed=true]:not([aria-label])") === null');

    setBrowserOffline($page, false);
});

test('sin red, descartar devuelve la tarjeta y muestra el toast de error', function (): void {
    Copypasta::factory()->create(['title' => 'Tarjeta que no se descarta']);
    signInInBrowser(User::factory()->create());

    $page = visitInteractive('/nuevos');

    setBrowserOffline($page, true);

    $page->click(__('ui.card.more'))
        ->click('@card-dismiss')
        ->waitForText(__('public.copy.action_failed'))
        ->assertSee('Tarjeta que no se descarta');

    setBrowserOffline($page, false);
});

test('sin red, abrir las carpetas muestra el toast de error', function (): void {
    Copypasta::factory()->create();
    signInInBrowser(User::factory()->create());

    $page = visitInteractive('/nuevos');

    setBrowserOffline($page, true);

    $page->click(__('ui.card.more'))
        ->click(__('public.folders.button'))
        ->waitForText(__('public.copy.action_failed'));

    setBrowserOffline($page, false);
});

test('si el portapapeles falla, copiar muestra el toast de error y no cuenta la copia', function (): void {
    $copypasta = Copypasta::factory()->create();

    $page = visitInteractive('/nuevos');
    $page->script('(() => { navigator.clipboard.writeText = async () => { throw new Error("denegado"); }; })()');

    $page->press(__('ui.card.copy'))->waitForText(__('public.copy.action_failed'));

    expect($copypasta->refresh()->copies_count)->toBe(0);
});

// Livewire requests ------------------------------------------------------------------------------------------------

test('una petición de Livewire sin red muestra el toast de error', function (): void {
    Copypasta::factory()->create();

    $page = visitInteractive('/');

    setBrowserOffline($page, true);

    $page->press(__('public.sorts.top_week'))->waitForText(__('public.copy.action_failed'));

    setBrowserOffline($page, false);
});

test('un 419 de Livewire abre el aviso en español con un botón para recargar, sin el cuadro en inglés', function (): void {
    Copypasta::factory()->create();

    $page = visitInteractive('/');
    $page->script('(() => { window.confirm = () => { window.__englishConfirmShown = true; return false; }; })()');
    interceptRequests($page, 'livewire', status: 419);

    $page->press(__('public.sorts.top_week'))
        ->waitForText(__('pwa.request_failed.expired_title'))
        ->assertSee(__('pwa.request_failed.expired_body'))
        ->assertPresent('@session-expired-reload')
        ->assertScript('window.__englishConfirmShown === undefined');
});

test('un 429 de Livewire muestra el toast "Vas demasiado rápido"', function (): void {
    Copypasta::factory()->create();

    $page = visitInteractive('/');
    interceptRequests($page, 'livewire', status: 429);

    $page->press(__('public.sorts.top_week'))->waitForText(__('pwa.request_failed.rate_limited'));
});

// Loading states ---------------------------------------------------------------------------------------------------

test('al cambiar de pestaña el feed enseña el skeleton mientras carga y lo retira después', function (): void {
    Copypasta::factory()->count(2)->create();

    $page = visitInteractive('/');
    interceptRequests($page, 'livewire', delay: 1500);

    $skeleton = 'document.querySelector("[data-test=feed-skeleton]")';

    $page->press(__('public.sorts.top_week'))
        ->assertScript("getComputedStyle({$skeleton}).display !== 'none'")
        ->assertScript("getComputedStyle({$skeleton}).display === 'none'");
});
