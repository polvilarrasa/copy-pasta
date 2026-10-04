<?php

declare(strict_types=1);

use App\Actions\HideCopypasta;
use App\Actions\ReportCopypasta;
use App\Enums\ModerationActionType;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Mail\CopypastaHiddenMail;
use App\Mail\ReportedMinorAlertMail;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

test('un miembro reporta un copy-pasta ajeno y el reporte queda pendiente', function (): void {
    $reporter = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    $report = app(ReportCopypasta::class)->handle($reporter, $copypasta, ReportReason::Spam, null);

    expect($report->status)->toBe(ReportStatus::Pending)
        ->and($report->reporter_id)->toBe($reporter->id)
        ->and($copypasta->refresh()->isHidden())->toBeFalse();
});

test('no se puede reportar el propio copy-pasta', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();

    app(ReportCopypasta::class)->handle($author, $copypasta, ReportReason::Spam, null);
})->throws(AuthorizationException::class);

test('sin email verificado no se puede reportar', function (): void {
    app(ReportCopypasta::class)->handle(User::factory()->unverified()->create(), Copypasta::factory()->create(), ReportReason::Spam, null);
})->throws(AuthorizationException::class);

test('no se reportan copy-pastas ocultos ni sin publicar', function (): void {
    $reporter = User::factory()->create();

    expect(fn () => app(ReportCopypasta::class)->handle($reporter, Copypasta::factory()->hidden()->create(), ReportReason::Spam, null))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(ReportCopypasta::class)->handle($reporter, Copypasta::factory()->unpublished()->create(), ReportReason::Spam, null))
        ->toThrow(AuthorizationException::class);
});

test('un miembro no tiene dos reportes pendientes del mismo copy-pasta', function (): void {
    $reporter = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    app(ReportCopypasta::class)->handle($reporter, $copypasta, ReportReason::Spam, null);

    app(ReportCopypasta::class)->handle($reporter, $copypasta, ReportReason::Other, 'Otro motivo válido');
})->throws(ValidationException::class);

test('el motivo "otro" exige un texto de entre 10 y 500 caracteres', function (): void {
    $reporter = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    expect(fn () => app(ReportCopypasta::class)->handle($reporter, $copypasta, ReportReason::Other, 'corto'))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(ReportCopypasta::class)->handle($reporter, $copypasta, ReportReason::Other, str_repeat('a', 501)))
        ->toThrow(ValidationException::class);

    expect(app(ReportCopypasta::class)->handle($reporter, $copypasta, ReportReason::Other, 'Motivo de diez')->details)
        ->toBe('Motivo de diez');
});

test('cinco reportes pendientes de usuarios distintos ocultan el copy-pasta', function (): void {
    Mail::fake();
    $copypasta = Copypasta::factory()->create();

    foreach (range(1, 5) as $_) {
        app(ReportCopypasta::class)->handle(User::factory()->create(), $copypasta, ReportReason::Spam, null);
    }

    $copypasta->refresh();

    expect($copypasta->isHidden())->toBeTrue()
        ->and($copypasta->hidden_reason)->toBe('Pendiente de revisión')
        ->and($copypasta->hidden_by_id)->toBeNull()
        ->and(Report::query()->where('copypasta_id', $copypasta->id)->pending()->count())->toBe(5)
        ->and(ModerationAction::query()->where('action', ModerationActionType::Hide)->whereNull('actor_id')->exists())->toBeTrue();
});

test('cuatro reportes pendientes no ocultan el copy-pasta', function (): void {
    $copypasta = Copypasta::factory()->create();

    foreach (range(1, 4) as $_) {
        app(ReportCopypasta::class)->handle(User::factory()->create(), $copypasta, ReportReason::Spam, null);
    }

    expect($copypasta->refresh()->isHidden())->toBeFalse();
});

test('un reporte por menores oculta el copy-pasta de inmediato y avisa a los admins', function (): void {
    Mail::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $copypasta = Copypasta::factory()->create();

    app(ReportCopypasta::class)->handle(User::factory()->create(), $copypasta, ReportReason::SexualContentMinors, null);

    expect($copypasta->refresh()->isHidden())->toBeTrue();

    Mail::assertQueued(ReportedMinorAlertMail::class, fn (ReportedMinorAlertMail $mail): bool => $mail->hasTo($admin->email));
});

test('el aviso de menores no llega a moderadores ni a admins sin verificar', function (): void {
    Mail::fake();
    User::factory()->moderator()->create();
    User::factory()->create(['role' => 'admin', 'email_verified_at' => null]);

    app(ReportCopypasta::class)->handle(User::factory()->create(), Copypasta::factory()->create(), ReportReason::SexualContentMinors, null);

    Mail::assertNotQueued(ReportedMinorAlertMail::class);
});

test('un miembro puede enviar como máximo diez reportes por hora', function (): void {
    $reporter = User::factory()->create();

    foreach (range(1, ReportCopypasta::MAX_REPORTS_PER_HOUR) as $_) {
        app(ReportCopypasta::class)->handle($reporter, Copypasta::factory()->create(), ReportReason::Spam, null);
    }

    app(ReportCopypasta::class)->handle($reporter, Copypasta::factory()->create(), ReportReason::Spam, null);
})->throws(ThrottleRequestsException::class);

test('un copy-pasta oculto a mano por el staff recibe el aviso al autor', function (): void {
    Mail::fake();
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();

    app(HideCopypasta::class)->handle(User::factory()->moderator()->create(), $copypasta, 'Spam');

    Mail::assertQueued(CopypastaHiddenMail::class, fn (CopypastaHiddenMail $mail): bool => $mail->hasTo($author->email));
});
