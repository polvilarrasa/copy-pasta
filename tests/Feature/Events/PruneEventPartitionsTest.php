<?php

declare(strict_types=1);

use App\Actions\DropExpiredEventPartitions;
use App\Actions\PrepareEventPartition;
use Illuminate\Support\Facades\Schema;

beforeEach(fn () => prepareEventPartitions());

test('borra las particiones de más de 13 meses y conserva las dentro del plazo', function (): void {
    $older = now()->startOfMonth()->subMonths(14);
    $withinRetention = now()->startOfMonth()->subMonths(12);

    app(PrepareEventPartition::class)->handle($older);
    app(PrepareEventPartition::class)->handle($withinRetention);

    $dropped = app(DropExpiredEventPartitions::class)->handle(now());

    expect($dropped)->toBe(1)
        ->and(Schema::hasTable(PrepareEventPartition::nameFor($older)))->toBeFalse()
        ->and(Schema::hasTable(PrepareEventPartition::nameFor($withinRetention)))->toBeTrue();
});

test('events:partitions crea el mes en curso y el siguiente sin fallar si ya existen', function (): void {
    $this->artisan('events:partitions')->assertSuccessful();
    $this->artisan('events:partitions')->assertSuccessful();

    expect(Schema::hasTable(PrepareEventPartition::nameFor(now())))->toBeTrue()
        ->and(Schema::hasTable(PrepareEventPartition::nameFor(now()->addMonth())))->toBeTrue();
});
