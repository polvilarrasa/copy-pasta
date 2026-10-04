<?php

declare(strict_types=1);

use App\Enums\FeedSort;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Queries\FeedQuery;

test('el feed excluye copy-pastas ocultos, borrados y sin publicar', function (): void {
    $visible = Copypasta::factory()->create();
    $hidden = Copypasta::factory()->hidden()->create();
    $unpublished = Copypasta::factory()->unpublished()->create();
    $deleted = Copypasta::factory()->create();
    $deleted->delete();

    $ids = FeedQuery::make()->sort(FeedSort::Newest)->builder()->pluck('id');

    expect($ids)
        ->toContain($visible->id)
        ->not->toContain($hidden->id)
        ->not->toContain($unpublished->id)
        ->not->toContain($deleted->id);
});

test('top_week solo incluye publicados en los últimos 7 días y ordena por score', function (): void {
    $lowScore = Copypasta::factory()->publishedDaysAgo(1)->create(['score' => 2]);
    $highScore = Copypasta::factory()->publishedDaysAgo(6)->create(['score' => 9]);
    $outsideWindow = Copypasta::factory()->publishedDaysAgo(8)->create(['score' => 100]);

    $ids = FeedQuery::make()->sort(FeedSort::TopWeek)->builder()->pluck('id');

    expect($ids->all())->toBe([$highScore->id, $lowScore->id])
        ->and($ids)->not->toContain($outsideWindow->id);
});

test('top_month incluye publicados en los últimos 30 días y excluye los anteriores', function (): void {
    $withinMonth = Copypasta::factory()->publishedDaysAgo(20)->create(['score' => 3]);
    $outsideMonth = Copypasta::factory()->publishedDaysAgo(40)->create(['score' => 50]);

    $ids = FeedQuery::make()->sort(FeedSort::TopMonth)->builder()->pluck('id');

    expect($ids)->toContain($withinMonth->id)->not->toContain($outsideMonth->id);
});

test('top_all ordena por score descendente y desempata por fecha de publicación', function (): void {
    $older = Copypasta::factory()->publishedDaysAgo(30)->create(['score' => 5]);
    $newer = Copypasta::factory()->publishedDaysAgo(2)->create(['score' => 5]);
    $best = Copypasta::factory()->publishedDaysAgo(90)->create(['score' => 10]);

    $ids = FeedQuery::make()->sort(FeedSort::TopAll)->builder()->pluck('id');

    expect($ids->all())->toBe([$best->id, $newer->id, $older->id]);
});

test('new ordena por fecha de publicación descendente', function (): void {
    $older = Copypasta::factory()->publishedDaysAgo(5)->create(['score' => 99]);
    $newer = Copypasta::factory()->publishedDaysAgo(1)->create(['score' => 0]);

    $ids = FeedQuery::make()->sort(FeedSort::Newest)->builder()->pluck('id');

    expect($ids->all())->toBe([$newer->id, $older->id]);
});

test('el orden aleatorio es reproducible con la misma semilla', function (): void {
    Copypasta::factory()->count(10)->create();

    $first = FeedQuery::make()->sort(FeedSort::Random, 'semilla-fija')->builder()->pluck('id')->all();
    $second = FeedQuery::make()->sort(FeedSort::Random, 'semilla-fija')->builder()->pluck('id')->all();

    expect($first)->toBe($second)->toHaveCount(10);
});

test('el filtro de etiquetas exige que estén presentes todas las seleccionadas', function (): void {
    $humor = Tag::factory()->create(['slug' => 'humor']);
    $anime = Tag::factory()->create(['slug' => 'anime']);

    $withBoth = Copypasta::factory()->create();
    $withBoth->tags()->attach([$humor->id, $anime->id]);

    $withOnlyHumor = Copypasta::factory()->create();
    $withOnlyHumor->tags()->attach([$humor->id]);

    $ids = FeedQuery::make()->sort(FeedSort::Newest)->tags(['humor', 'anime'])->builder()->pluck('id');

    expect($ids)->toContain($withBoth->id)->not->toContain($withOnlyHumor->id);
});

test('la búsqueda encuentra texto ignorando acentos y mayúsculas', function (): void {
    $match = Copypasta::factory()->create([
        'title' => 'Receta del café',
        'body' => 'Un texto sobre azúcar y cafeína.',
    ]);
    $noMatch = Copypasta::factory()->create([
        'title' => 'Otro tema',
        'body' => 'Contenido sin relación.',
    ]);

    $ids = FeedQuery::make()->sort(FeedSort::Newest)->search('CAFE')->builder()->pluck('id');

    expect($ids)->toContain($match->id)->not->toContain($noMatch->id);
});

test('la búsqueda con acentos encuentra texto escrito sin ellos y al revés', function (): void {
    $copypasta = Copypasta::factory()->create([
        'title' => 'Canción infantil',
        'body' => 'La canción de cuna.',
    ]);

    $withoutAccents = FeedQuery::make()->sort(FeedSort::Newest)->search('cancion')->builder()->pluck('id');
    $withAccents = FeedQuery::make()->sort(FeedSort::Newest)->search('canción')->builder()->pluck('id');

    expect($withoutAccents)->toContain($copypasta->id)
        ->and($withAccents)->toContain($copypasta->id);
});

test('nsfw(false) excluye copy-pastas marcados como nsfw y nsfw(true) los incluye', function (): void {
    $sfw = Copypasta::factory()->create();
    $nsfw = Copypasta::factory()->nsfw()->create();

    $withoutNsfw = FeedQuery::make()->sort(FeedSort::Newest)->nsfw(false)->builder()->pluck('id');
    $withNsfw = FeedQuery::make()->sort(FeedSort::Newest)->nsfw(true)->builder()->pluck('id');

    expect($withoutNsfw)->toContain($sfw->id)->not->toContain($nsfw->id)
        ->and($withNsfw)->toContain($sfw->id)->toContain($nsfw->id);
});
