<?php

declare(strict_types=1);

use App\Enums\ReportReason;
use App\Models\Copypasta;
use App\Models\Report;

test('un visitante ve el formulario de aviso de un copy-pasta visible', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->get(route('notice.create', $copypasta))->assertOk();
});

test('el formulario de aviso de un copy-pasta oculto responde 404', function (): void {
    $copypasta = Copypasta::factory()->hidden()->create();

    $this->get(route('notice.create', $copypasta))->assertNotFound();
});

test('un visitante envía un aviso con su email y queda en la cola sin reportero', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->post(route('notice.store', $copypasta), [
        'email' => 'visitante@example.com',
        'reason' => ReportReason::PersonalData->value,
        'details' => null,
    ])->assertRedirect(route('notice.create', $copypasta));

    expect(Report::query()->where('copypasta_id', $copypasta->getKey())->sole())
        ->reporter_id->toBeNull()
        ->contact_email->toBe('visitante@example.com');
});

test('el aviso valida que el email tenga formato', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->post(route('notice.store', $copypasta), [
        'email' => 'no-es-un-email',
        'reason' => ReportReason::Spam->value,
    ])->assertSessionHasErrors('email');
});

test('el aviso anónimo tiene límite de envíos por IP', function (): void {
    $copypasta = Copypasta::factory()->create();

    foreach (range(1, 5) as $_) {
        $this->post(route('notice.store', $copypasta), [
            'email' => 'visitante@example.com',
            'reason' => ReportReason::Spam->value,
        ]);
    }

    $this->post(route('notice.store', $copypasta), [
        'email' => 'visitante@example.com',
        'reason' => ReportReason::Spam->value,
    ])->assertStatus(429);
});
