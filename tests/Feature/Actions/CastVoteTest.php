<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Models\Copypasta;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

function counters(Copypasta $copypasta): array
{
    $copypasta->refresh();

    return [
        'up' => $copypasta->upvotes_count,
        'down' => $copypasta->downvotes_count,
        'score' => $copypasta->score,
    ];
}

test('la secuencia +1, +1 de nuevo y -1 deja los contadores correctos en cada paso', function (): void {
    $voter = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $vote = app(CastVote::class);

    expect($vote->handle($voter, $copypasta, 1))->toBe(1)
        ->and(counters($copypasta))->toBe(['up' => 1, 'down' => 0, 'score' => 1]);

    expect($vote->handle($voter, $copypasta, 1))->toBeNull()
        ->and(counters($copypasta))->toBe(['up' => 0, 'down' => 0, 'score' => 0]);

    expect($vote->handle($voter, $copypasta, -1))->toBe(-1)
        ->and(counters($copypasta))->toBe(['up' => 0, 'down' => 1, 'score' => -1]);
});

test('votar lo contrario cambia el voto sin duplicarlo', function (): void {
    $voter = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $vote = app(CastVote::class);

    $vote->handle($voter, $copypasta, 1);

    expect($vote->handle($voter, $copypasta, -1))->toBe(-1)
        ->and(counters($copypasta))->toBe(['up' => 0, 'down' => 1, 'score' => -1])
        ->and(Vote::query()->where('copypasta_id', $copypasta->id)->count())->toBe(1);
});

test('los contadores coinciden con el recuento real tras votos de varios miembros', function (): void {
    $copypasta = Copypasta::factory()->create();
    $vote = app(CastVote::class);

    foreach (range(1, 6) as $index) {
        $vote->handle(User::factory()->create(), $copypasta, $index % 3 === 0 ? -1 : 1);
    }

    $upvotes = Vote::query()->where('copypasta_id', $copypasta->id)->where('value', 1)->count();
    $downvotes = Vote::query()->where('copypasta_id', $copypasta->id)->where('value', -1)->count();

    expect(counters($copypasta))->toBe(['up' => $upvotes, 'down' => $downvotes, 'score' => $upvotes - $downvotes]);
});

test('el voto toma un bloqueo de fila sobre el copy-pasta', function (): void {
    $copypasta = Copypasta::factory()->create();
    $queries = [];

    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    app(CastVote::class)->handle(User::factory()->create(), $copypasta, 1);

    expect(collect($queries)->filter(fn (string $sql): bool => str_contains(strtolower($sql), 'for update'))->count())
        ->toBeGreaterThanOrEqual(2);
});

test('un miembro no vota su propio copy-pasta', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $author->id]);

    app(CastVote::class)->handle($author, $copypasta, 1);
})->throws(AuthorizationException::class);

test('no se vota un copy-pasta oculto', function (): void {
    $copypasta = Copypasta::factory()->hidden()->create();

    app(CastVote::class)->handle(User::factory()->create(), $copypasta, 1);
})->throws(AuthorizationException::class);

test('un miembro sin email verificado sí puede votar', function (): void {
    $copypasta = Copypasta::factory()->create();

    expect(app(CastVote::class)->handle(User::factory()->unverified()->create(), $copypasta, 1))->toBe(1);
});
