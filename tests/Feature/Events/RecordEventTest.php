<?php

declare(strict_types=1);

use App\Actions\RecordEvent;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\TrackedEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

beforeEach(fn () => prepareEventPartitions());

test('registra un evento de usuario con su tipo, su copy-pasta y su contexto', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    app(RecordEvent::class)->handle(EventType::Copy, $member, $copypasta, ['source' => 'top_week', 'position' => 3]);

    $event = TrackedEvent::query()->sole();

    expect($event)
        ->type->toBe(EventType::Copy)
        ->user_id->toBe($member->getKey())
        ->copypasta_id->toBe($copypasta->getKey())
        ->visitor_hash->toBeNull()
        ->context->toBe(['source' => 'top_week', 'position' => 3]);
});

test('un visitante sin cuenta queda con un hash y sin usuario', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]))->assertOk();

    $event = TrackedEvent::query()->sole();

    expect($event)
        ->type->toBe(EventType::DetailView)
        ->user_id->toBeNull()
        ->visitor_hash->toMatch('/^[a-f0-9]{64}$/');
});

test('el hash de un visitante cambia cada día y la respuesta no usa cookies', function (): void {
    $copypasta = Copypasta::factory()->create();
    $url = route('copypastas.show', [$copypasta, $copypasta->slug]);

    $this->get($url)->assertOk()->assertCookieMissing('visitor');
    $this->travelTo(now()->addDay());
    $this->get($url)->assertOk();

    [$firstDay, $secondDay] = TrackedEvent::query()->orderBy('id')->pluck('visitor_hash')->all();

    expect($firstDay)->not->toBe($secondDay);
});

test('un fallo al escribir el evento se registra en el log y no lanza excepción', function (): void {
    Log::spy();
    TrackedEvent::creating(fn () => throw new RuntimeException('disco lleno'));

    app(RecordEvent::class)->handle(EventType::Share, User::factory()->create(), Copypasta::factory()->create());

    expect(TrackedEvent::query()->count())->toBe(0);

    Log::shouldHaveReceived('error')->once()->withArgs(fn (string $message): bool => $message === 'No se pudo registrar un evento');
});

test('un fallo al escribir el evento no revierte la transacción en la que ocurre', function (): void {
    $copypasta = Copypasta::factory()->create(['copies_count' => 0]);
    TrackedEvent::creating(fn () => throw new RuntimeException('disco lleno'));

    DB::transaction(function () use ($copypasta): void {
        Copypasta::query()->whereKey($copypasta->getKey())->increment('copies_count');

        app(RecordEvent::class)->handle(EventType::Copy, null, $copypasta);
    });

    expect($copypasta->refresh()->copies_count)->toBe(1);
});
