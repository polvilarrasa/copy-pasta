<?php

declare(strict_types=1);

use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\TrackedEvent;

beforeEach(fn () => prepareEventPartitions());

test('una visita humana al detalle registra detail_view', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]))->assertOk();

    expect(TrackedEvent::query()->where('type', EventType::DetailView)->count())->toBe(1);
});

test('un rastreador ve el detalle pero no genera detail_view', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->withHeaders(['User-Agent' => 'WhatsApp/2.23.20.0 A'])
        ->get(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->assertOk();

    expect(TrackedEvent::query()->where('type', EventType::DetailView)->count())->toBe(0);
});

test('un bot con ?ref= no atribuye la referencia', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->withHeaders(['User-Agent' => 'Discordbot/2.0'])
        ->get(route('copypastas.show', [$copypasta, $copypasta->slug]).'?ref=ABCDEF12')
        ->assertOk();

    expect(TrackedEvent::query()->count())->toBe(0);
});

test('una visita humana con ?ref= guarda la referencia en el evento', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]).'?ref=ABCDEF12')->assertOk();

    expect(TrackedEvent::query()->sole()->context)->toMatchArray(['ref' => 'ABCDEF12']);
});

test('una petición sin user agent no genera detail_view', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->withHeaders(['User-Agent' => ''])
        ->get(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->assertOk();

    expect(TrackedEvent::query()->count())->toBe(0);
});
