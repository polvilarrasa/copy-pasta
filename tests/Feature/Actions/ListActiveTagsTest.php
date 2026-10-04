<?php

declare(strict_types=1);

use App\Actions\ListActiveTags;
use App\Actions\SaveTag;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

test('la lista de etiquetas activas solo incluye las activas, ordenadas por nombre', function (): void {
    Tag::factory()->create(['name' => 'Zeta', 'is_active' => true]);
    Tag::factory()->create(['name' => 'Alfa', 'is_active' => true]);
    Tag::factory()->create(['name' => 'Oculta', 'is_active' => false]);

    expect(app(ListActiveTags::class)->handle()->pluck('name')->all())->toBe(['Alfa', 'Zeta']);
});

test('la lista se cachea y se invalida al guardar una etiqueta', function (): void {
    Tag::factory()->create(['name' => 'Alfa', 'is_active' => true]);
    app(ListActiveTags::class)->handle();

    Tag::factory()->create(['name' => 'Beta', 'is_active' => true]);
    expect(app(ListActiveTags::class)->handle()->pluck('name')->all())->toBe(['Alfa']);

    app(SaveTag::class)->handle(User::factory()->admin()->create(), ['name' => 'Gamma', 'color' => 'blue', 'is_active' => true]);

    expect(app(ListActiveTags::class)->handle()->pluck('name')->all())->toBe(['Alfa', 'Beta', 'Gamma']);
    expect(Cache::has(ListActiveTags::CACHE_KEY))->toBeTrue();
});

test('la lista sobrevive a un ida y vuelta por el store de caché real', function (): void {
    Cache::setDefaultDriver('file');

    try {
        Cache::forget(ListActiveTags::CACHE_KEY);
        Tag::factory()->create(['name' => 'Alfa', 'is_active' => true]);

        app(ListActiveTags::class)->handle();
        $fromStore = app(ListActiveTags::class)->handle();

        expect($fromStore->first())->toBeInstanceOf(Tag::class)
            ->and($fromStore->first()->name)->toBe('Alfa');
    } finally {
        Cache::forget(ListActiveTags::CACHE_KEY);
        Cache::setDefaultDriver('array');
    }
});
