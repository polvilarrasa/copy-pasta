<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use App\Models\Vote;

test('recalcula votos, puntuación y favoritos desde las tablas reales', function (): void {
    $copypasta = Copypasta::factory()->create();
    $copypasta->forceFill(['upvotes_count' => 9, 'downvotes_count' => 9, 'score' => 0, 'favorites_count' => 9])->save();

    Vote::factory()->upvote()->create(['copypasta_id' => $copypasta->getKey()]);
    Vote::factory()->upvote()->create(['copypasta_id' => $copypasta->getKey()]);
    Vote::factory()->downvote()->create(['copypasta_id' => $copypasta->getKey()]);
    Folder::ensureDefaultFor(User::factory()->create())->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);
    Folder::ensureDefaultFor(User::factory()->create())->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    $this->artisan('app:recalculate-counters')->assertSuccessful();

    expect($copypasta->refresh())
        ->upvotes_count->toBe(2)
        ->downvotes_count->toBe(1)
        ->score->toBe(1)
        ->favorites_count->toBe(2);
});

test('no cuenta como favorito una entrada de una carpeta que no es la predeterminada', function (): void {
    $copypasta = Copypasta::factory()->create(['favorites_count' => 5]);
    $folder = Folder::factory()->create(['user_id' => User::factory()->create()->getKey(), 'is_default' => false]);
    $folder->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    $this->artisan('app:recalculate-counters')->assertSuccessful();

    expect($copypasta->refresh()->favorites_count)->toBe(0);
});
