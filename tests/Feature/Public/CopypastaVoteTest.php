<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\User;
use App\Models\Vote;

test('un anónimo que vota es redirigido a login', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->post(route('copypastas.vote', $copypasta), ['value' => 1])
        ->assertRedirect(route('login'));

    expect(Vote::query()->count())->toBe(0);
});

test('un miembro vota y recibe el score real y su voto', function (): void {
    $copypasta = Copypasta::factory()->create(['score' => 0]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('copypastas.vote', $copypasta), ['value' => 1])
        ->assertOk()
        ->assertJson(['score' => 1, 'upvotes_count' => 1, 'downvotes_count' => 0, 'my_vote' => 1]);
});

test('el valor del voto debe ser +1 o -1', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->actingAs(User::factory()->create())
        ->postJson(route('copypastas.vote', $copypasta), ['value' => 0])
        ->assertUnprocessable();
});

test('no se puede votar lo propio por HTTP', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $author->id]);

    $this->actingAs($author)
        ->postJson(route('copypastas.vote', $copypasta), ['value' => 1])
        ->assertForbidden();
});

test('a los 60 votos por minuto el siguiente responde 429', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    foreach (range(1, 60) as $attempt) {
        $this->actingAs($member)->postJson(route('copypastas.vote', $copypasta), ['value' => 1])->assertOk();
    }

    $this->actingAs($member)
        ->postJson(route('copypastas.vote', $copypasta), ['value' => 1])
        ->assertStatus(429);
});

test('el feed y el detalle muestran el voto del visitante', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['title' => 'Titulo votado']);
    $copypasta->votes()->create(['user_id' => $member->id, 'value' => -1]);

    expect(Copypasta::query()->withViewerState($member)->whereKey($copypasta->id)->first()->my_vote)->toBe(-1)
        ->and(Copypasta::query()->withViewerState(null)->whereKey($copypasta->id)->first()->my_vote)->toBeNull();

    $this->actingAs($member)
        ->get(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->assertOk()
        ->assertSee('myVote: -1,', false);
});

test('la web muestra el modal de inicio de sesión para anónimos al votar', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->assertOk()
        ->assertSee(__('public.login_modal.vote'))
        ->assertSee('authenticated: false,', false);
});
