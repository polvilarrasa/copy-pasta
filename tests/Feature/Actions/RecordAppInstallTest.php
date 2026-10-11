<?php

declare(strict_types=1);

use App\Actions\RecordAppInstall;
use App\Enums\EventType;
use App\Models\TrackedEvent;
use App\Models\User;

beforeEach(fn () => prepareEventPartitions());

test('registra app_install con el usuario', function (): void {
    $user = User::factory()->create();

    app(RecordAppInstall::class)->handle($user);

    expect(TrackedEvent::query()->where('type', EventType::AppInstall)->sole()->user_id)->toBe($user->id);
});

test('registra app_install de un anónimo con el hash de visitante y sin usuario', function (): void {
    app(RecordAppInstall::class)->handle();

    $event = TrackedEvent::query()->where('type', EventType::AppInstall)->sole();

    expect($event->user_id)->toBeNull()->and($event->visitor_hash)->not->toBeNull();
});

test('el navegador avisa con un POST al evento appinstalled y se registra', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('pwa.installed'))
        ->assertNoContent();

    expect(TrackedEvent::query()->where('type', EventType::AppInstall)->count())->toBe(1);
});

test('un visitante sin sesión también puede registrar la instalación', function (): void {
    $this->post(route('pwa.installed'))->assertNoContent();

    expect(TrackedEvent::query()->where('type', EventType::AppInstall)->whereNull('user_id')->count())->toBe(1);
});

test('el endpoint de instalación está limitado a 5 avisos por minuto', function (): void {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('pwa.installed'))->assertNoContent();
    }

    $this->post(route('pwa.installed'))->assertTooManyRequests();
});
