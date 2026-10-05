<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;

test('un anónimo que reporta es redirigido al login', function (): void {
    $this->post(route('copypastas.report', Copypasta::factory()->create()), ['reason' => 'spam'])
        ->assertRedirect(route('login'));

    expect(Report::query()->count())->toBe(0);
});

test('un miembro envía un reporte y recibe confirmación', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->actingAs(User::factory()->established()->create())
        ->postJson(route('copypastas.report', $copypasta), ['reason' => 'spam'])
        ->assertCreated()
        ->assertJsonStructure(['message']);

    expect(Report::query()->where('copypasta_id', $copypasta->id)->count())->toBe(1);
});

test('el reporte valida el motivo y el texto de "otro"', function (): void {
    $copypasta = Copypasta::factory()->create();
    $member = User::factory()->established()->create();

    $this->actingAs($member)
        ->postJson(route('copypastas.report', $copypasta), ['reason' => 'inventado'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);

    $this->actingAs($member)
        ->postJson(route('copypastas.report', $copypasta), ['reason' => 'other', 'details' => 'corto'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['details']);
});

test('no se puede reportar el propio copy-pasta', function (): void {
    $author = User::factory()->established()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();

    $this->actingAs($author)
        ->postJson(route('copypastas.report', $copypasta), ['reason' => 'spam'])
        ->assertForbidden();
});

test('un segundo reporte pendiente del mismo copy-pasta se rechaza', function (): void {
    $copypasta = Copypasta::factory()->create();
    $member = User::factory()->established()->create();
    Report::factory()->for($copypasta)->create(['reporter_id' => $member->id]);

    $this->actingAs($member)
        ->postJson(route('copypastas.report', $copypasta), ['reason' => 'spam'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
});
