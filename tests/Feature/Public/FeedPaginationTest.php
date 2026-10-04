<?php

declare(strict_types=1);

use App\Livewire\Feed;
use App\Models\Copypasta;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * Ids currently rendered by the feed, in display order.
 *
 * @return array<int, string>
 */
function idsOnCurrentPage(Testable $component): array
{
    $ids = [];

    $component->assertViewHas('copypastas', function ($copypastas) use (&$ids): bool {
        $ids = $copypastas->pluck('id')->all();

        return true;
    });

    return $ids;
}

test('el orden aleatorio carga tres tramos sin repetir copy-pastas', function (): void {
    Copypasta::factory()->count(45)->create();

    $component = Livewire::test(Feed::class);
    $firstBatch = idsOnCurrentPage($component);

    $secondBatch = idsOnCurrentPage($component->call('loadMore'));
    $thirdBatch = idsOnCurrentPage($component->call('loadMore'));

    expect($firstBatch)->toHaveCount(20)
        ->and($secondBatch)->toHaveCount(40)
        ->and($thirdBatch)->toHaveCount(45)
        ->and(array_slice($thirdBatch, 0, 20))->toBe($firstBatch)
        ->and(array_unique($thirdBatch))->toHaveCount(45);
});

test('barajar regenera la semilla y vuelve a la primera página', function (): void {
    Copypasta::factory()->count(25)->create();

    $component = Livewire::test(Feed::class)->call('loadMore');
    $seedBefore = session('feed.random_seed');

    $component->call('shuffle');

    expect(session('feed.random_seed'))->not->toBe($seedBefore);
    expect(idsOnCurrentPage($component))->toHaveCount(20);
});
