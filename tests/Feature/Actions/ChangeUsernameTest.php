<?php

declare(strict_types=1);

use App\Actions\ChangeUsername;
use App\Enums\EventType;
use App\Models\TrackedEvent;
use App\Models\User;
use App\Models\UsernameHistory;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => prepareEventPartitions());

test('cambiar el username guarda la fecha del cambio y el nombre anterior en el historial', function (): void {
    $member = User::factory()->create(['username' => 'ana']);

    app(ChangeUsername::class)->handle($member, 'ana_nueva');

    $member->refresh();

    expect($member->username)->toBe('ana_nueva')
        ->and($member->username_changed_at)->not->toBeNull()
        ->and(UsernameHistory::query()->sole())
        ->username->toBe('ana')
        ->user_id->toBe($member->getKey());
});

test('cambiar el username registra un evento username_change', function (): void {
    $member = User::factory()->create(['username' => 'ana']);

    app(ChangeUsername::class)->handle($member, 'ana_nueva');

    expect(TrackedEvent::query()->sole())
        ->type->toBe(EventType::UsernameChange)
        ->user_id->toBe($member->getKey());
});

test('un segundo cambio dentro de 30 días falla', function (): void {
    $member = User::factory()->create(['username' => 'ana']);
    app(ChangeUsername::class)->handle($member, 'ana_nueva');

    app(ChangeUsername::class)->handle($member->refresh(), 'ana_otra');
})->throws(ValidationException::class);

test('se puede cambiar otra vez a los 30 días', function (): void {
    $member = User::factory()->create(['username' => 'ana']);
    app(ChangeUsername::class)->handle($member, 'ana_nueva');

    $this->travel(30)->days();
    app(ChangeUsername::class)->handle($member->refresh(), 'ana_otra');

    expect($member->refresh()->username)->toBe('ana_otra');
});

test('repetir el mismo username no consume el cupo ni deja historial', function (): void {
    $member = User::factory()->create(['username' => 'ana']);

    app(ChangeUsername::class)->handle($member, 'ana');

    expect($member->refresh()->username_changed_at)->toBeNull()
        ->and(UsernameHistory::query()->count())->toBe(0);
});

test('no se puede tomar un username que ya usa otra cuenta', function (): void {
    User::factory()->create(['username' => 'ocupado']);
    $member = User::factory()->create(['username' => 'ana']);

    app(ChangeUsername::class)->handle($member, 'ocupado');
})->throws(ValidationException::class);

test('un nombre antiguo vuelve a estar libre para otra cuenta', function (): void {
    $member = User::factory()->create(['username' => 'ana']);
    app(ChangeUsername::class)->handle($member, 'ana_nueva');

    $newcomer = User::factory()->create(['username' => 'ana_nueva2']);
    app(ChangeUsername::class)->handle($newcomer, 'ana');

    expect($newcomer->refresh()->username)->toBe('ana');
});
