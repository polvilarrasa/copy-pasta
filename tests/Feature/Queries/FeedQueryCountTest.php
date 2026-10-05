<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

/**
 * Counts the SQL statements a request runs before its response, so the home feed must stay within five, for guests and
 * members alike. The feed impression counter writes after the response is sent and is not part of that budget.
 */
function queriesFor(string $uri): int
{
    $count = 0;
    DB::listen(function (QueryExecuted $query) use (&$count): void {
        if (! str_starts_with($query->sql, 'INSERT INTO copypasta_daily_stats')) {
            $count++;
        }
    });

    test()->get($uri)->assertOk();

    return $count;
}

beforeEach(function (): void {
    $tags = Tag::factory()->count(3)->create();

    foreach (range(1, 12) as $_) {
        Copypasta::factory()->create()->tags()->attach($tags->random(2)->pluck('id'));
    }
});

test('el feed de la home ejecuta cinco consultas como máximo para un invitado', function (): void {
    expect(queriesFor('/'))->toBeLessThanOrEqual(5);
});

test('el feed de la home ejecuta cinco consultas como máximo para un miembro', function (): void {
    $this->actingAs(User::factory()->create());

    expect(queriesFor('/'))->toBeLessThanOrEqual(5);
});
