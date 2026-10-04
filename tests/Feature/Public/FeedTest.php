<?php

declare(strict_types=1);

use App\Http\Controllers\Public\NsfwConfirmationController;
use App\Livewire\Feed;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;

test('la home muestra copy-pastas publicados y no los ocultos', function (): void {
    Copypasta::factory()->create(['title' => 'Titulo visible']);
    Copypasta::factory()->hidden()->create(['title' => 'Titulo retirado']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Titulo visible')
        ->assertDontSee('Titulo retirado');
});

test('los anónimos no ven NSFW por defecto ni aunque pidan la URL sin cookie de +18', function (): void {
    Copypasta::factory()->nsfw()->create(['title' => 'Titulo adulto']);

    Livewire::test(Feed::class)->assertDontSee('Titulo adulto');

    Livewire::withQueryParams(['nsfw' => 1])
        ->test(Feed::class)
        ->assertDontSee('Titulo adulto');
});

test('un anónimo con cookie de +18 y nsfw activo ve el contenido adulto', function (): void {
    Copypasta::factory()->nsfw()->create(['title' => 'Titulo adulto']);

    Livewire::withQueryParams(['nsfw' => 1])
        ->withCookie(NsfwConfirmationController::COOKIE, '1')
        ->test(Feed::class)
        ->assertSee('Titulo adulto');
});

test('un usuario ve NSFW solo si lo tiene activado en su preferencia', function (): void {
    Copypasta::factory()->nsfw()->create(['title' => 'Titulo adulto']);

    Livewire::actingAs(User::factory()->create(['show_nsfw' => false]))
        ->test(Feed::class)
        ->assertDontSee('Titulo adulto');

    Livewire::actingAs(User::factory()->create(['show_nsfw' => true, 'nsfw_confirmed_at' => now()]))
        ->test(Feed::class)
        ->assertSee('Titulo adulto');
});

test('filtra por etiquetas combinadas e ignora las desactivadas', function (): void {
    $php = Tag::factory()->create(['slug' => 'php', 'name' => 'Etiqueta PHP']);
    $inactive = Tag::factory()->inactive()->create(['slug' => 'antigua']);
    $js = Tag::factory()->create(['slug' => 'js']);

    $withPhp = Copypasta::factory()->create(['title' => 'Titulo con php']);
    $withPhp->tags()->attach([$php->id]);

    $withJs = Copypasta::factory()->create(['title' => 'Titulo con js']);
    $withJs->tags()->attach([$js->id]);

    Livewire::withQueryParams(['tags' => 'php,antigua'])
        ->test(Feed::class)
        ->assertSee('Titulo con php')
        ->assertDontSee('Titulo con js');
});

test('busca en título y cuerpo sin distinguir acentos', function (): void {
    Copypasta::factory()->create(['title' => 'Canción fácil', 'body' => 'Texto cualquiera']);
    Copypasta::factory()->create(['title' => 'Otra cosa', 'body' => 'Texto distinto']);

    Livewire::withQueryParams(['q' => 'cancion facil'])
        ->test(Feed::class)
        ->assertSee('Canción fácil')
        ->assertDontSee('Otra cosa');
});

test('el orden nuevos devuelve primero el copy-pasta publicado más recientemente', function (): void {
    $older = Copypasta::factory()->publishedDaysAgo(10)->create();
    $newer = Copypasta::factory()->publishedDaysAgo(1)->create();

    Livewire::withQueryParams(['sort' => 'new'])
        ->test(Feed::class)
        ->assertViewHas('copypastas', fn ($copypastas): bool => $copypastas->pluck('id')->all() === [$newer->id, $older->id]);
});

test('la ruta del top semanal fija el orden y excluye lo publicado hace más de siete días', function (): void {
    $highScoreOld = Copypasta::factory()->publishedDaysAgo(20)->create(['score' => 99]);
    $lowScoreRecent = Copypasta::factory()->publishedDaysAgo(2)->create(['score' => 1]);

    $this->get('/top/semana')->assertOk();

    Livewire::test(Feed::class, ['fixedSort' => 'top_week'])
        ->assertViewHas('copypastas', fn ($copypastas): bool => $copypastas->pluck('id')->all() === [$lowScoreRecent->id])
        ->assertDontSee($highScoreOld->title);
});

test('la ruta de etiqueta filtra por la etiqueta activa y responde 404 si está desactivada', function (): void {
    $php = Tag::factory()->create(['slug' => 'php']);
    $inactive = Tag::factory()->inactive()->create(['slug' => 'antigua']);

    $copypasta = Copypasta::factory()->create(['title' => 'Titulo etiquetado']);
    $copypasta->tags()->attach($php->id);

    $this->get('/etiqueta/php')->assertOk()->assertSee('Titulo etiquetado');
    $this->get('/etiqueta/antigua')->assertNotFound();
});

test('las rutas alias de orden responden 200', function (): void {
    foreach (['/top', '/top/mes', '/nuevos'] as $path) {
        $this->get($path)->assertOk();
    }
});
