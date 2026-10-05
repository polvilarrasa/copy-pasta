<?php

declare(strict_types=1);

use App\Livewire\Feed;
use App\Models\Copypasta;
use Livewire\Livewire;

test('el orden aleatorio continúa desde la semilla y da la vuelta al principio', function (): void {
    $early = Copypasta::factory()->create();
    $middle = Copypasta::factory()->create();
    $late = Copypasta::factory()->create();

    $early->forceFill(['random_key' => 10])->save();
    $middle->forceFill(['random_key' => 60])->save();
    $late->forceFill(['random_key' => 90])->save();

    Livewire::test(Feed::class)
        ->set('seed', 50)
        ->assertViewHas('copypastas', fn ($copypastas): bool => $copypastas->pluck('id')->all() === [
            $middle->id,
            $late->id,
            $early->id,
        ]);
});

test('una semilla en el mismo punto devuelve el mismo orden entre peticiones', function (): void {
    Copypasta::factory()->count(30)->create();

    $first = Livewire::test(Feed::class)->set('seed', 1_000_000)->viewData('copypastas')->pluck('id')->all();
    $second = Livewire::test(Feed::class)->set('seed', 1_000_000)->viewData('copypastas')->pluck('id')->all();

    expect($first)->toBe($second)->toHaveCount(20);
});

test('los copy-pastas nuevos reciben una clave aleatoria dentro del rango', function (): void {
    $copypasta = Copypasta::factory()->create();

    expect($copypasta->refresh()->random_key)->toBeInt()
        ->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual(2147483646);
});
