<?php

declare(strict_types=1);

use App\Actions\UpdateNotificationPreferences;
use App\Enums\NotificationType;
use App\Models\User;
use Livewire\Livewire;

test('todos los tipos están activados por defecto', function (): void {
    $member = User::factory()->create();

    foreach (NotificationType::cases() as $type) {
        expect($member->wantsNotification($type))->toBeTrue();
    }
});

test('los tipos de moderación no se pueden desactivar ni por la Action', function (): void {
    $member = User::factory()->create();

    app(UpdateNotificationPreferences::class)->handle($member, [
        'milestone' => false,
        'copypasta_hidden' => false,
        'copypasta_restored' => false,
        'inventado' => false,
    ]);

    expect($member->refresh()->notification_prefs)->toBe(['milestone' => false])
        ->and($member->wantsNotification(NotificationType::Milestone))->toBeFalse()
        ->and($member->wantsNotification(NotificationType::CopypastaHidden))->toBeTrue()
        ->and($member->wantsNotification(NotificationType::CopypastaRestored))->toBeTrue();
});

test('un tipo ausente conserva su valor al guardar otro', function (): void {
    $member = User::factory()->create(['notification_prefs' => ['milestone' => false]]);

    app(UpdateNotificationPreferences::class)->handle($member, ['report_accepted' => false]);

    expect($member->refresh()->notification_prefs)->toBe(['milestone' => false, 'report_accepted' => false]);
});

test('la página de ajustes muestra los opcionales y los de moderación activados y deshabilitados con su explicación', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('notifications.edit'))
        ->assertOk()
        ->assertSee(__('notifications.types.milestone.label'))
        ->assertSee(__('notifications.types.copypasta_hidden.label'))
        ->assertSee(__('settings.notifications.mandatory_hint'))
        ->assertSeeInOrder(['name="mandatory.copypasta_hidden"', 'checked', 'disabled'], false);
});

test('el interruptor guarda la preferencia', function (): void {
    $member = User::factory()->create();

    Livewire::actingAs($member)->test('pages::settings.notifications')
        ->assertSet('prefs.milestone', true)
        ->set('prefs.milestone', false);

    expect($member->refresh()->wantsNotification(NotificationType::Milestone))->toBeFalse();
});

test('un invitado no entra en los ajustes de notificaciones', function (): void {
    $this->get(route('notifications.edit'))->assertRedirect(route('login'));
});
