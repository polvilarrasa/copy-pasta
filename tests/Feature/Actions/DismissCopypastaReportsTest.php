<?php

declare(strict_types=1);

use App\Actions\DismissCopypastaReports;
use App\Actions\HideCopypasta;
use App\Enums\ModerationActionType;
use App\Enums\ReportStatus;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('descartar reportes los marca como rechazados y lo registra', function (): void {
    $moderator = User::factory()->moderator()->create();
    $copypasta = Copypasta::factory()->create();
    Report::factory()->for($copypasta)->count(2)->create();

    $dismissed = app(DismissCopypastaReports::class)->handle($moderator, $copypasta);

    expect($dismissed)->toBe(2)
        ->and(Report::query()->where('copypasta_id', $copypasta->id)->where('status', ReportStatus::Rejected)->where('resolved_by_id', $moderator->id)->count())->toBe(2)
        ->and(ModerationAction::query()->where('action', ModerationActionType::DismissReports)->where('actor_id', $moderator->id)->exists())->toBeTrue();
});

test('ocultar un copy-pasta acepta sus reportes pendientes', function (): void {
    $moderator = User::factory()->moderator()->create();
    $copypasta = Copypasta::factory()->create();
    Report::factory()->for($copypasta)->count(3)->create();

    app(HideCopypasta::class)->handle($moderator, $copypasta, 'Spam');

    expect(Report::query()->where('copypasta_id', $copypasta->id)->where('status', ReportStatus::Accepted)->count())->toBe(3)
        ->and(Report::query()->where('copypasta_id', $copypasta->id)->pending()->count())->toBe(0);
});

test('la resolución en bloque solo toca los reportes pendientes de cada copy-pasta', function (): void {
    $moderator = User::factory()->moderator()->create();
    $first = Copypasta::factory()->create();
    $second = Copypasta::factory()->create();
    $resolvedBefore = Report::factory()->for($second)->resolved(ReportStatus::Accepted)->create();
    Report::factory()->for($first)->count(2)->create();
    Report::factory()->for($second)->create();

    $dismissed = collect([$first, $second])
        ->sum(fn (Copypasta $copypasta): int => app(DismissCopypastaReports::class)->handle($moderator, $copypasta));

    expect($dismissed)->toBe(3)
        ->and($resolvedBefore->refresh()->status)->toBe(ReportStatus::Accepted)
        ->and(Report::query()->pending()->count())->toBe(0);
});

test('sin reportes pendientes no se registra ninguna acción', function (): void {
    $moderator = User::factory()->moderator()->create();

    expect(app(DismissCopypastaReports::class)->handle($moderator, Copypasta::factory()->create()))->toBe(0)
        ->and(ModerationAction::query()->where('action', ModerationActionType::DismissReports)->exists())->toBeFalse();
});

test('un miembro normal no descarta reportes', function (): void {
    app(DismissCopypastaReports::class)->handle(User::factory()->create(), Copypasta::factory()->create());
})->throws(AuthorizationException::class);
