<?php

declare(strict_types=1);

use App\Livewire\Feed;
use App\Models\Copypasta;
use App\Models\TrackedEvent;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(fn () => prepareEventPartitions());

test('una página del feed suma una impresión a cada copy-pasta mostrado sin crear eventos', function (): void {
    $copypastas = Copypasta::factory()->count(3)->create();

    Livewire::test(Feed::class);
    app()->terminate();

    expect(DB::table('copypasta_daily_stats')->where('date', now()->toDateString())->pluck('impressions', 'copypasta_id')->all())
        ->toEqualCanonicalizing($copypastas->mapWithKeys(fn (Copypasta $copypasta): array => [$copypasta->getKey() => 1])->all())
        ->and(TrackedEvent::query()->count())->toBe(0);
});

test('cargar más suma impresiones solo a los copy-pastas nuevos de la página', function (): void {
    Copypasta::factory()->count(25)->create();

    $component = Livewire::test(Feed::class);
    app()->terminate();

    $component->call('loadMore');
    app()->terminate();

    expect(DB::table('copypasta_daily_stats')->sum('impressions'))->toBe(25)
        ->and(DB::table('copypasta_daily_stats')->where('impressions', '>', 1)->exists())->toBeFalse();
});
