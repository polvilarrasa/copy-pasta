<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('un usuario verificado puede reportar un copy-pasta ajeno', function (): void {
    $reporter = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    expect(Gate::forUser($reporter)->allows('create', [Report::class, $copypasta]))->toBeTrue();
});

test('un usuario no puede reportar su propio copy-pasta', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();

    expect(Gate::forUser($author)->allows('create', [Report::class, $copypasta]))->toBeFalse();
});

test('un usuario sin email verificado no puede reportar', function (): void {
    $reporter = User::factory()->unverified()->create();
    $copypasta = Copypasta::factory()->create();

    expect(Gate::forUser($reporter)->allows('create', [Report::class, $copypasta]))->toBeFalse();
});

test('un usuario normal no ve ni resuelve reportes', function (): void {
    $user = User::factory()->create();
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
