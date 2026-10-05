<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('un usuario verificado puede reportar un copy-pasta ajeno', function (): void {
    $reporter = User::factory()->established()->create();
    $copypasta = Copypasta::factory()->create();

    expect(Gate::forUser($reporter)->allows('create', [Report::class, $copypasta]))->toBeTrue();
});

test('un usuario no puede reportar su propio copy-pasta', function (): void {
    $author = User::factory()->established()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();

    expect(Gate::forUser($author)->allows('create', [Report::class, $copypasta]))->toBeFalse();
});

test('un usuario sin email verificado no puede reportar', function (): void {
    $reporter = User::factory()->unverified()->create();
    $copypasta = Copypasta::factory()->create();

    expect(Gate::forUser($reporter)->allows('create', [Report::class, $copypasta]))->toBeFalse();
});

test('un usuario normal no ve ni resuelve reportes', function (): void {
    $user = User::factory()->established()->create();
    $report = Report::factory()->create();

    expect(Gate::forUser($user)->allows('view', $report))->toBeFalse();
    expect(Gate::forUser($user)->allows('resolve', $report))->toBeFalse();
});

test('el staff ve y resuelve reportes', function (): void {
    $moderator = User::factory()->moderator()->create();
    $report = Report::factory()->create();

    expect(Gate::forUser($moderator)->allows('view', $report))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('resolve', $report))->toBeTrue();
});

test('una cuenta de menos de 72 horas no puede reportar', function (): void {
    $newcomer = User::factory()->create(['created_at' => now()->subHours(71)]);
    $copypasta = Copypasta::factory()->create();

    expect(Gate::forUser($newcomer)->allows('create', [Report::class, $copypasta]))->toBeFalse();
});

test('tras tres reportes rechazados en 30 días no puede reportar, y vuelve a poder cuando caducan', function (): void {
    $reporter = User::factory()->established()->create();
    $copypasta = Copypasta::factory()->create();

    Report::factory()->count(3)->create([
        'reporter_id' => $reporter->getKey(),
        'status' => ReportStatus::Rejected,
        'resolved_at' => now()->subDays(2),
    ]);

    expect(Gate::forUser($reporter)->allows('create', [Report::class, $copypasta]))->toBeFalse();

    $this->travel(31)->days();

    expect(Gate::forUser($reporter)->allows('create', [Report::class, $copypasta]))->toBeTrue();
});

test('dos reportes rechazados no bastan para perder el derecho a reportar', function (): void {
    $reporter = User::factory()->established()->create();
    $copypasta = Copypasta::factory()->create();

    Report::factory()->count(2)->create([
        'reporter_id' => $reporter->getKey(),
        'status' => ReportStatus::Rejected,
        'resolved_at' => now()->subDays(2),
    ]);

    expect(Gate::forUser($reporter)->allows('create', [Report::class, $copypasta]))->toBeTrue();
});
