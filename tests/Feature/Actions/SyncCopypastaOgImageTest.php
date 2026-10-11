<?php

declare(strict_types=1);

use App\Actions\ConcealCopypasta;
use App\Actions\DeleteCopypasta;
use App\Actions\HideCopypasta;
use App\Actions\MarkCopypastaNsfw;
use App\Actions\PublishCopypasta;
use App\Actions\RestoreCopypasta;
use App\Actions\SyncCopypastaOgImage;
use App\Actions\UpdateCopypasta;
use App\Jobs\SyncCopypastaOgImageJob;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use App\Support\OgImagePath;
use App\Support\OgImageRenderer;
use App\Support\OgImageRenderFailed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config(['og.enabled' => true, 'og.disk' => 'public']);
    Storage::fake('public');

    $this->renderer = $this->mock(OgImageRenderer::class, function ($mock): void {
        $mock->shouldReceive('render')->andReturnUsing(fn (string $title, string $body): string => 'PNG:'.$title.'|'.$body);
    });
});

function publishFor(User $author, array $overrides = []): Copypasta
{
    return app(PublishCopypasta::class)->handle($author, [
        'title' => 'Título original',
        'body' => 'Cuerpo original',
        'is_nsfw' => false,
        'tag_ids' => [Tag::factory()->create()->getKey()],
        ...$overrides,
    ]);
}

test('publicar genera la imagen y la guarda con un nombre que lleva el hash del contenido', function (): void {
    $author = User::factory()->create();

    $copypasta = publishFor($author)->refresh();

    expect($copypasta->og_image_path)
        ->toMatch('#^og/'.$copypasta->getKey().'-[0-9a-f]{16}\.png$#')
        ->toBe(OgImagePath::for($copypasta));

    Storage::disk('public')->assertExists($copypasta->og_image_path);

    expect($copypasta->ogImageUrl())->toStartWith('http')->toEndWith($copypasta->og_image_path);
});

test('la imagen se genera en cola, nunca dentro de la petición', function (): void {
    Bus::fake([SyncCopypastaOgImageJob::class]);

    $copypasta = publishFor(User::factory()->create());

    Bus::assertDispatched(SyncCopypastaOgImageJob::class, fn (SyncCopypastaOgImageJob $job): bool => $job->copypastaId === $copypasta->getKey());
    expect($copypasta->refresh()->og_image_path)->toBeNull();
});

test('editar el texto genera una imagen con otro nombre y borra la anterior', function (): void {
    $author = User::factory()->create();
    $copypasta = publishFor($author)->refresh();
    $first = $copypasta->og_image_path;

    app(UpdateCopypasta::class)->handle($author, $copypasta, [
        'title' => 'Título editado',
        'body' => 'Cuerpo original',
        'is_nsfw' => false,
        'tag_ids' => $copypasta->tags()->pluck('tags.id')->all(),
    ]);

    $second = $copypasta->refresh()->og_image_path;

    expect($second)->not->toBe($first)->toBe(OgImagePath::for($copypasta));
    Storage::disk('public')->assertExists($second);
    Storage::disk('public')->assertMissing($first);
});

test('guardar sin cambiar el texto conserva el mismo nombre de fichero', function (): void {
    $author = User::factory()->create();
    $copypasta = publishFor($author)->refresh();
    $first = $copypasta->og_image_path;

    app(UpdateCopypasta::class)->handle($author, $copypasta, [
        'title' => 'Título original',
        'body' => 'Cuerpo original',
        'is_nsfw' => false,
        'tag_ids' => $copypasta->tags()->pluck('tags.id')->all(),
    ]);

    expect($copypasta->refresh()->og_image_path)->toBe($first);
});

test('ocultarlo elimina su imagen y vuelve la genérica; restaurarlo la genera de nuevo', function (): void {
    $author = User::factory()->create();
    $staff = User::factory()->moderator()->create();
    $copypasta = publishFor($author)->refresh();
    $path = $copypasta->og_image_path;

    app(HideCopypasta::class)->handle($staff, $copypasta, 'Incumple las normas');

    $copypasta->refresh();
    Storage::disk('public')->assertMissing($path);
    expect($copypasta->og_image_path)->toBeNull()->and($copypasta->ogImageUrl())->toBe(asset('images/og-generic.png'));

    app(RestoreCopypasta::class)->handle($staff, $copypasta);

    Storage::disk('public')->assertExists($copypasta->refresh()->og_image_path);
});

test('una ocultación automática también elimina la imagen', function (): void {
    $copypasta = publishFor(User::factory()->create())->refresh();

    app(ConcealCopypasta::class)->handle(null, $copypasta, 'Reportes automáticos', acceptsPendingReports: false);

    Storage::disk('public')->assertMissing($copypasta->og_image_path ?? 'og/none');
    expect($copypasta->refresh()->og_image_path)->toBeNull();
});

test('borrarlo elimina su imagen', function (): void {
    $author = User::factory()->create();
    $copypasta = publishFor($author)->refresh();
    $path = $copypasta->og_image_path;

    app(DeleteCopypasta::class)->handle($author, $copypasta);

    Storage::disk('public')->assertMissing($path);
    expect(Copypasta::withTrashed()->find($copypasta->getKey())->og_image_path)->toBeNull();
});

test('un NSFW no tiene imagen propia: usa la genérica, y marcarlo la elimina', function (): void {
    $nsfw = publishFor(User::factory()->create(), ['is_nsfw' => true])->refresh();

    expect($nsfw->og_image_path)->toBeNull()->and($nsfw->ogImageUrl())->toBe(asset('images/og-generic.png'));

    $normal = publishFor(User::factory()->create())->refresh();
    $path = $normal->og_image_path;

    app(MarkCopypastaNsfw::class)->handle(User::factory()->moderator()->create(), $normal, true);

    Storage::disk('public')->assertMissing($path);
    expect($normal->refresh()->ogImageUrl())->toBe(asset('images/og-generic.png'));
});

test('mientras no existe la imagen se usa la genérica', function (): void {
    expect(Copypasta::factory()->create()->ogImageUrl())->toBe(asset('images/og-generic.png'));
});

test('si el dibujo falla se usa la genérica, se borra la imagen anterior y se registra en el log', function (): void {
    $author = User::factory()->create();
    $copypasta = publishFor($author)->refresh();
    $previous = $copypasta->og_image_path;

    $this->mock(OgImageRenderer::class, fn ($mock) => $mock->shouldReceive('render')->andThrow(new OgImageRenderFailed('límite superado')));
    Log::shouldReceive('warning')->once()->withArgs(fn (string $message, array $context): bool => $context['copypasta_id'] === $copypasta->getKey());

    $copypasta->forceFill(['title' => 'Otro título'])->save();
    app(SyncCopypastaOgImage::class)->handle($copypasta->getKey());

    Storage::disk('public')->assertMissing($previous);
    expect($copypasta->refresh()->og_image_path)->toBeNull();
});

test('el nombre del fichero cambia con la versión de la plantilla', function (): void {
    $copypasta = Copypasta::factory()->create();
    $current = OgImagePath::for($copypasta);
    $nextVersion = 'og/'.$copypasta->getKey().'-'.substr(hash('sha256', (OgImageRenderer::TEMPLATE_VERSION + 1)."\0".$copypasta->title."\0".$copypasta->body), 0, 16).'.png';

    expect($current)->not->toBe($nextVersion);
});

test('con la generación desactivada no se hace nada', function (): void {
    config(['og.enabled' => false]);

    expect(publishFor(User::factory()->create())->refresh()->og_image_path)->toBeNull();
});

test('el detalle declara og:image absoluta con la imagen propia y twitter:card grande', function (): void {
    $copypasta = publishFor(User::factory()->create())->refresh();

    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->assertOk()
        ->assertSee('<meta property="og:image" content="'.url(Storage::disk('public')->url($copypasta->og_image_path)).'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
});

test('el detalle de un NSFW declara la imagen genérica', function (): void {
    $nsfw = Copypasta::factory()->nsfw()->create();

    $this->get(route('copypastas.show', [$nsfw, $nsfw->slug]))
        ->assertOk()
        ->assertSee('<meta property="og:image" content="'.asset('images/og-generic.png').'">', false);
});

test('app:generate-og-images encola por lotes solo lo que falta o ha cambiado, y con --force todo', function (): void {
    $upToDate = publishFor(User::factory()->create())->refresh();
    $missing = Copypasta::factory()->create();
    $outdated = Copypasta::factory()->create(['og_image_path' => 'og/vieja-0000000000000000.png']);
    Copypasta::factory()->nsfw()->create();
    Copypasta::factory()->hidden()->create();

    Bus::fake();
    $this->artisan('app:generate-og-images')->assertSuccessful();

    Bus::assertBatched(function ($batch) use ($missing, $outdated, $upToDate): bool {
        $ids = $batch->jobs->map(fn (SyncCopypastaOgImageJob $job): string => $job->copypastaId)->all();

        return count($ids) === 2 && in_array($missing->getKey(), $ids, true) && in_array($outdated->getKey(), $ids, true) && ! in_array($upToDate->getKey(), $ids, true);
    });

    Bus::fake();
    $this->artisan('app:generate-og-images --force')->assertSuccessful();

    Bus::assertBatched(fn ($batch): bool => $batch->jobs->count() === 3 && $batch->jobs->every(fn (SyncCopypastaOgImageJob $job): bool => $job->force));
});

test('el job con force dibuja de nuevo aunque el nombre no cambie', function (): void {
    $copypasta = publishFor(User::factory()->create())->refresh();

    $this->mock(OgImageRenderer::class, fn ($mock) => $mock->shouldReceive('render')->once()->andReturn('PNG-NUEVO'));

    app(SyncCopypastaOgImage::class)->handle($copypasta->getKey(), force: true);

    expect(Storage::disk('public')->get($copypasta->og_image_path))->toBe('PNG-NUEVO');
});
