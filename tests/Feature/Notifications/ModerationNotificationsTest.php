<?php

declare(strict_types=1);

use App\Actions\ChangeUserRole;
use App\Actions\DismissCopypastaReports;
use App\Actions\HideCopypasta;
use App\Actions\MarkCopypastaNsfw;
use App\Actions\ReportCopypasta;
use App\Actions\RestoreCopypasta;
use App\Enums\ModerationActionType;
use App\Enums\NotificationType;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Mail\CopypastaHiddenMail;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function notificationTypes(User $user): array
{
    return $user->notifications()->orderBy('created_at')->pluck('type')->all();
}

test('ocultar un copy-pasta notifica al autor además del email', function (): void {
    Mail::fake();
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();

    app(HideCopypasta::class)->handle(User::factory()->moderator()->create(), $copypasta, 'Spam');

    expect(notificationTypes($author))->toBe([NotificationType::CopypastaHidden->value])
        ->and($author->notifications()->sole()->data)->toEqual(['type' => 'copypasta_hidden', 'copypasta_id' => $copypasta->getKey()]);
    Mail::assertQueued(CopypastaHiddenMail::class);
});

test('el autor sigue recibiendo el aviso de oculto aunque tenga todo lo opcional desactivado', function (): void {
    $author = User::factory()->create(['notification_prefs' => ['milestone' => false, 'report_accepted' => false, 'trusted_promotion' => false, 'copypasta_hidden' => false]]);

    app(HideCopypasta::class)->handle(User::factory()->moderator()->create(), Copypasta::factory()->for($author, 'user')->create(), 'Spam');

    expect(notificationTypes($author))->toBe([NotificationType::CopypastaHidden->value]);
});

test('un autor baneado no recibe la notificación de oculto', function (): void {
    $author = User::factory()->banned()->create();

    app(HideCopypasta::class)->handle(User::factory()->moderator()->create(), Copypasta::factory()->for($author, 'user')->create(), 'Spam');

    expect($author->notifications()->count())->toBe(0);
});

test('restaurar un copy-pasta notifica al autor una sola vez', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->hidden()->create();

    app(RestoreCopypasta::class)->handle(User::factory()->moderator()->create(), $copypasta);

    expect(notificationTypes($author))->toBe([NotificationType::CopypastaRestored->value]);
});

test('descartar los reportes de una ocultación automática restaura por RestoreCopypasta y notifica una sola vez', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();
    foreach (range(1, 5) as $_) {
        app(ReportCopypasta::class)->handle(User::factory()->established()->create(), $copypasta, ReportReason::Spam, null);
    }
    expect(notificationTypes($author))->toBe([NotificationType::CopypastaHidden->value]);

    app(DismissCopypastaReports::class)->handle(User::factory()->moderator()->withTwoFactor()->create(), $copypasta);

    expect(notificationTypes($author))->toBe([NotificationType::CopypastaHidden->value, NotificationType::CopypastaRestored->value])
        ->and(ModerationAction::query()->where('action', ModerationActionType::Restore)->where('subject_id', $copypasta->getKey())->count())->toBe(1);
});

test('ocultar a mano acepta los reportes y notifica a cada reportero registrado, no a los anónimos', function (): void {
    $copypasta = Copypasta::factory()->create();
    [$first, $second] = User::factory()->count(2)->create();
    Report::factory()->for($copypasta)->create(['reporter_id' => $first->id]);
    Report::factory()->for($copypasta)->create(['reporter_id' => $second->id]);
    Report::factory()->for($copypasta)->create(['reporter_id' => null, 'contact_email' => 'anon@example.com']);

    app(HideCopypasta::class)->handle(User::factory()->moderator()->create(), $copypasta, 'Spam');

    expect(notificationTypes($first))->toBe([NotificationType::ReportAccepted->value])
        ->and(notificationTypes($second))->toBe([NotificationType::ReportAccepted->value])
        ->and(Report::query()->where('status', ReportStatus::Accepted)->count())->toBe(3);
});

test('un reporte descartado no notifica al reportero', function (): void {
    $reporter = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    Report::factory()->for($copypasta)->create(['reporter_id' => $reporter->id]);

    app(DismissCopypastaReports::class)->handle(User::factory()->moderator()->create(), $copypasta);

    expect($reporter->notifications()->count())->toBe(0);
});

test('un reportero con "reporte aceptado" desactivado no recibe la notificación', function (): void {
    $reporter = User::factory()->create(['notification_prefs' => ['report_accepted' => false]]);
    $copypasta = Copypasta::factory()->create();
    Report::factory()->for($copypasta)->create(['reporter_id' => $reporter->id]);

    app(HideCopypasta::class)->handle(User::factory()->moderator()->create(), $copypasta, 'Spam');

    expect($reporter->notifications()->count())->toBe(0);
});

test('marcar como NSFW acepta solo los reportes de NSFW sin marcar, con su log y la notificación', function (): void {
    $moderator = User::factory()->moderator()->create();
    $copypasta = Copypasta::factory()->create();
    $nsfwReporter = User::factory()->create();
    $spamReporter = User::factory()->create();
    $nsfwReport = Report::factory()->for($copypasta)->create(['reporter_id' => $nsfwReporter->id, 'reason' => ReportReason::NsfwUnmarked]);
    $spamReport = Report::factory()->for($copypasta)->create(['reporter_id' => $spamReporter->id, 'reason' => ReportReason::Spam]);

    app(MarkCopypastaNsfw::class)->handle($moderator, $copypasta, true);

    expect($nsfwReport->refresh())
        ->status->toBe(ReportStatus::Accepted)
        ->resolved_by_id->toBe($moderator->id)
        ->resolved_at->not->toBeNull()
        ->and($spamReport->refresh()->status)->toBe(ReportStatus::Pending)
        ->and(notificationTypes($nsfwReporter))->toBe([NotificationType::ReportAccepted->value])
        ->and($spamReporter->notifications()->count())->toBe(0)
        ->and(ModerationAction::query()->where('action', ModerationActionType::MarkNsfw)->sole()->meta)->toBe(['accepted_reports' => 1]);
});

test('quitar el NSFW no acepta ni rechaza reportes', function (): void {
    $copypasta = Copypasta::factory()->nsfw()->create();
    $report = Report::factory()->for($copypasta)->create(['reason' => ReportReason::NsfwUnmarked]);

    app(MarkCopypastaNsfw::class)->handle(User::factory()->moderator()->create(), $copypasta, false);

    expect($report->refresh()->status)->toBe(ReportStatus::Pending);
});

test('los reportes de NSFW aceptados cuentan en la fiabilidad del reportero', function (): void {
    $reporter = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    Report::factory()->for($copypasta)->create(['reporter_id' => $reporter->id, 'reason' => ReportReason::NsfwUnmarked]);

    app(MarkCopypastaNsfw::class)->handle(User::factory()->moderator()->create(), $copypasta, true);

    expect(Report::query()->where('reporter_id', $reporter->id)->where('status', ReportStatus::Accepted)->count())->toBe(1);
});

test('ascender a usuario de confianza notifica al miembro', function (): void {
    $member = User::factory()->create();

    app(ChangeUserRole::class)->handle(User::factory()->admin()->create(), $member, Role::Trusted);

    expect(notificationTypes($member))->toBe([NotificationType::TrustedPromotion->value]);
});

test('el ascenso a confianza no notifica si el rol no cambia ni si baja desde el staff', function (): void {
    $admin = User::factory()->admin()->create();
    $trusted = User::factory()->trusted()->create();
    $moderator = User::factory()->moderator()->create();

    app(ChangeUserRole::class)->handle($admin, $trusted, Role::Trusted);
    app(ChangeUserRole::class)->handle($admin, $moderator, Role::Trusted);

    expect($trusted->notifications()->count())->toBe(0)
        ->and($moderator->notifications()->count())->toBe(0);
});

test('con "ascenso a confianza" desactivado no se crea la notificación', function (): void {
    $member = User::factory()->create(['notification_prefs' => ['trusted_promotion' => false]]);

    app(ChangeUserRole::class)->handle(User::factory()->admin()->create(), $member, Role::Trusted);

    expect($member->notifications()->count())->toBe(0);
});
