<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\User;

test('un anónimo que guarda en favoritos es redirigido a login', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->post(route('copypastas.favorite', $copypasta))->assertRedirect(route('login'));

    expect($copypasta->refresh()->favorites_count)->toBe(0);
});

test('un miembro alterna el favorito y recibe el contador', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['favorites_count' => 0]);

    $this->actingAs($member)
        ->postJson(route('copypastas.favorite', $copypasta))
        ->assertOk()
        ->assertJson(['favorited' => true, 'favorites_count' => 1]);

    $this->actingAs($member)
        ->postJson(route('copypastas.favorite', $copypasta))
        ->assertOk()
        ->assertJson(['favorited' => false, 'favorites_count' => 0]);
});

test('el feed marca como favorito lo que está en la carpeta del visitante', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    $this->actingAs($member)->postJson(route('copypastas.favorite', $copypasta))->assertOk();

    expect(Copypasta::query()->withViewerState($member)->whereKey($copypasta->id)->first()->is_favorite)->toBeTrue()
        ->and(Copypasta::query()->withViewerState(User::factory()->create())->whereKey($copypasta->id)->first()->is_favorite)->toBeFalse();
});
