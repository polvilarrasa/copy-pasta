<?php

declare(strict_types=1);

use App\Actions\ComputeUserStats;
use App\Enums\ReportStatus;
use App\Models\Copypasta;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Inserts one `copypasta_daily_stats` row directly, bypassing the event aggregator: these tests exercise the
 * reading side only.
 *
 * @param  array<string, int>  $values
 */
function seedDailyStat(Copypasta $copypasta, CarbonInterface $date, array $values): void
{
    DB::table('copypasta_daily_stats')->insert([
        'copypasta_id' => $copypasta->getKey(),
        'date' => $date->toDateString(),
        'views' => $values['views'] ?? 0,
        'copies' => $values['copies'] ?? 0,
        'shares' => $values['shares'] ?? 0,
        'upvotes' => $values['upvotes'] ?? 0,
        'downvotes' => $values['downvotes'] ?? 0,
        'favorites' => $values['favorites'] ?? 0,
    ]);
}

test('los totales de 30 días coinciden con las filas sembradas y el periodo anterior sin datos muestra "—"', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->for($user)->create();

    seedDailyStat($copypasta, now()->subDays(1), ['copies' => 10, 'upvotes' => 3, 'downvotes' => 1, 'favorites' => 2, 'shares' => 1, 'views' => 50]);
    seedDailyStat($copypasta, now()->subDays(10), ['copies' => 20, 'upvotes' => 2, 'favorites' => 1, 'views' => 10]);
    seedDailyStat($copypasta, now()->subDays(20), ['copies' => 5, 'upvotes' => 1, 'views' => 5]);

    $stats = app(ComputeUserStats::class)->handle($user);

    expect($stats['kpis']['copies']['total'])->toBe(35)
        ->and($stats['kpis']['votes']['total'])->toBe((3 + 2 + 1) - 1)
        ->and($stats['kpis']['saves']['total'])->toBe(3)
        ->and($stats['kpis']['shares']['total'])->toBe(1)
        ->and($stats['visits'])->toBe(65)
        ->and($stats['kpis']['copies']['delta'])->toBeNull();
});

test('la variación compara con los 30 días anteriores sin dividir entre cero', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->for($user)->create();

    seedDailyStat($copypasta, now()->subDays(1), ['copies' => 20]);
    seedDailyStat($copypasta, now()->subDays(40), ['copies' => 10]);

    $stats = app(ComputeUserStats::class)->handle($user);

    expect($stats['kpis']['copies']['delta'])->toBe(100.0)
        ->and($stats['kpis']['votes']['delta'])->toBeNull();
});

test('las estadísticas incluyen los copy-pastas ocultos del usuario y excluyen los borrados', function (): void {
    $user = User::factory()->create();
    $hidden = Copypasta::factory()->for($user)->hidden()->create();
    $deleted = Copypasta::factory()->for($user)->create();

    seedDailyStat($hidden, now()->subDays(1), ['copies' => 7]);
    seedDailyStat($deleted, now()->subDays(1), ['copies' => 99]);
    $deleted->delete();

    $stats = app(ComputeUserStats::class)->handle($user);

    expect($stats['kpis']['copies']['total'])->toBe(7);
});

test('el que más ha crecido en 7 días es el de mayor suma de copias y votos netos de la semana', function (): void {
    $user = User::factory()->create();
    $winner = Copypasta::factory()->for($user)->create(['title' => 'El que más crece']);
    $loser = Copypasta::factory()->for($user)->create(['title' => 'El que menos crece']);

    seedDailyStat($winner, now()->subDays(2), ['copies' => 50, 'upvotes' => 10]);
    seedDailyStat($loser, now()->subDays(2), ['copies' => 1]);
    seedDailyStat($winner, now()->subDays(20), ['copies' => 500]);

    $stats = app(ComputeUserStats::class)->handle($user);

    expect($stats['fastestGrowing']['title'])->toBe('El que más crece')
        ->and($stats['fastestGrowing']['growth'])->toBe(60);
});

test('no hay "el que más crece" cuando nadie tuvo actividad en la semana', function (): void {
    $user = User::factory()->create();
    Copypasta::factory()->for($user)->create();

    expect(app(ComputeUserStats::class)->handle($user)['fastestGrowing'])->toBeNull();
});

test('las 3 etiquetas con más copias se ordenan por las copias de los copy-pastas del usuario', function (): void {
    $user = User::factory()->create();
    $popular = Tag::factory()->create(['name' => 'humor']);
    $niche = Tag::factory()->create(['name' => 'nicho']);

    $copypasta = Copypasta::factory()->for($user)->create(['copies_count' => 40]);
    $copypasta->tags()->attach($popular);

    $other = Copypasta::factory()->for($user)->create(['copies_count' => 5]);
    $other->tags()->attach($niche);

    $stats = app(ComputeUserStats::class)->handle($user);

    expect($stats['topTags'][0]['name'])->toBe('humor')
        ->and($stats['topTags'][0]['copies'])->toBe(40);
});

test('la fiabilidad de reportes calcula aceptados entre resueltos y el progreso hacia usuario de confianza', function (): void {
    $user = User::factory()->create();
    Report::factory()->for($user, 'reporter')->resolved(ReportStatus::Accepted)->count(9)->create();
    Report::factory()->for($user, 'reporter')->resolved(ReportStatus::Rejected)->count(1)->create();
    Report::factory()->for($user, 'reporter')->create(); // pending, does not count

    $stats = app(ComputeUserStats::class)->handle($user);

    expect($stats['reliability'])->toBe([
        'isTrusted' => false,
        'resolved' => 10,
        'accepted' => 9,
        'percentage' => 90.0,
        'qualifies' => true,
    ]);
});

test('un usuario de confianza lo muestra aunque no cumpla los requisitos de reportes', function (): void {
    $user = User::factory()->trusted()->create();

    expect(app(ComputeUserStats::class)->handle($user)['reliability']['isTrusted'])->toBeTrue();
});

test('el cómputo hace como máximo 6 consultas y se cachea 10 minutos', function (): void {
    $user = User::factory()->create();
    Copypasta::factory()->for($user)->create();

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    app(ComputeUserStats::class)->handle($user);
    expect($queries)->toHaveCount(5);

    $queries = [];
    app(ComputeUserStats::class)->handle($user);
    expect($queries)->toBeEmpty();
});
