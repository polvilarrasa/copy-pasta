<?php

declare(strict_types=1);

use App\Models\Copypasta;

test('copiar incrementa el contador una vez por hora y visitante', function (): void {
    $copypasta = Copypasta::factory()->create(['copies_count' => 0]);

    $this->post(route('copypastas.copy', $copypasta))->assertNoContent();
    $this->post(route('copypastas.copy', $copypasta))->assertNoContent();

    expect($copypasta->refresh()->copies_count)->toBe(1);

    $this->travel(1)->hours();

    $this->post(route('copypastas.copy', $copypasta))->assertNoContent();

    expect($copypasta->refresh()->copies_count)->toBe(2);
});

test('no cuenta copias de un copy-pasta oculto', function (): void {
    $copypasta = Copypasta::factory()->hidden()->create(['copies_count' => 0]);

    $this->post(route('copypastas.copy', $copypasta))->assertNotFound();

    expect($copypasta->refresh()->copies_count)->toBe(0);
});
