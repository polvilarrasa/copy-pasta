<?php

declare(strict_types=1);

use App\Enums\ReportReason;
use App\Mail\CopypastaHiddenMail;
use App\Mail\ReportedMinorAlertMail;
use App\Models\Copypasta;
use App\Models\Report;

test('el correo de ocultación al autor muestra el título y el motivo', function (): void {
    $copypasta = Copypasta::factory()->hidden('Spam repetido')->create(['title' => 'Mi copy-pasta']);

    $mail = new CopypastaHiddenMail($copypasta->load('user'));

    expect($mail->envelope()->subject)->toContain('Mi copy-pasta')
        ->and($mail->render())->toContain('Spam repetido')
        ->and($mail->render())->toContain('Mi copy-pasta');
});

test('el aviso de menores enlaza con la cola de reportes', function (): void {
    $report = Report::factory()->create(['reason' => ReportReason::SexualContentMinors]);

    $html = (new ReportedMinorAlertMail($report))->render();

    expect($html)->toContain($report->copypasta->title)
        ->and($html)->toContain('/admin/cola-reportes');
});
