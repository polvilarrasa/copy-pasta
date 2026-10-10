<?php

declare(strict_types=1);

use App\Actions\ImpersonateUser;
use App\Actions\MarkNotificationsRead;
use App\Actions\OpenNotification;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\TrackedEvent;
use App\Models\User;
use App\Notifications\CopypastaHiddenNotification;
use App\Notifications\TrustedPromotionNotification;
use App\Support\UnreadNotificationCount;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(fn () => prepareEventPartitions());

/**
 * Acts as a member from an admin session, the way the staff review an account.
 */
function startReviewingAs(User $member): void
{
    $admin = User::factory()->admin()->create();
    test()->actingAs($admin);
    app(ImpersonateUser::class)->handle($admin, $member);
}

test('abrir una notificación la marca como leída, registra notification_open con el tipo y devuelve el destino', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->for($member, 'user')->hidden()->create();
    $member->notify(new CopypastaHiddenNotification($copypasta));
    $notification = $member->notifications()->sole();

    $url = app(OpenNotification::class)->handle($member, $notification->id);

    expect($url)->toBe(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->and($notification->refresh()->read_at)->not->toBeNull()
        ->and(TrackedEvent::query()->sole())
        ->type->toBe(EventType::NotificationOpen)
        ->user_id->toBe($member->id)
        ->context->toBe(['type' => 'copypasta_hidden']);
});

test('nadie abre ni marca la notificación de otro miembro', function (): void {
    $owner = User::factory()->create();
    $owner->notify(new TrustedPromotionNotification);
    $notification = $owner->notifications()->sole();

    expect(fn () => app(OpenNotification::class)->handle(User::factory()->create(), $notification->id))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(MarkNotificationsRead::class)->one(User::factory()->create(), $notification))
        ->toThrow(AuthorizationException::class);

    expect($notification->refresh()->read_at)->toBeNull();
});

test('marcar todas como leídas solo toca las del miembro', function (): void {
    $member = User::factory()->create();
    $other = User::factory()->create();
    $member->notify(new TrustedPromotionNotification);
    $member->notify(new TrustedPromotionNotification);
    $other->notify(new TrustedPromotionNotification);

    expect(app(MarkNotificationsRead::class)->all($member))->toBe(2)
        ->and($member->unreadNotifications()->count())->toBe(0)
        ->and($other->unreadNotifications()->count())->toBe(1);
});

test('el contador cacheado se invalida al crear y al leer notificaciones', function (): void {
    $member = User::factory()->create();
    $count = app(UnreadNotificationCount::class);

    expect($count->get($member))->toBe(0);

    $member->notify(new TrustedPromotionNotification);
    expect($count->get($member))->toBe(1);

    app(OpenNotification::class)->handle($member, $member->notifications()->sole()->id);
    expect($count->get($member))->toBe(0);

    $member->notify(new TrustedPromotionNotification);
    app(MarkNotificationsRead::class)->all($member);
    expect($count->get($member))->toBe(0);
});

test('durante una impersonación abrir y marcar todas no cambian nada ni registran el evento', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->for($member, 'user')->create();
    $member->notify(new CopypastaHiddenNotification($copypasta));
    $notification = $member->notifications()->sole();
    startReviewingAs($member);

    $url = app(OpenNotification::class)->handle($member, $notification->id);
    $marked = app(MarkNotificationsRead::class)->all($member);

    expect($url)->not->toBeNull()
        ->and($marked)->toBe(0)
        ->and($notification->refresh()->read_at)->toBeNull()
        ->and(app(UnreadNotificationCount::class)->get($member))->toBe(1)
        ->and(TrackedEvent::query()->count())->toBe(0);
});

test('el límite de acciones sobre notificaciones corta el exceso', function (): void {
    $member = User::factory()->create();
    RateLimiter::clear('notifications-read:'.$member->id);
    foreach (range(1, MarkNotificationsRead::MAX_PER_MINUTE) as $_) {
        RateLimiter::hit('notifications-read:'.$member->id, 60);
    }

    app(MarkNotificationsRead::class)->all($member);
})->throws(ThrottleRequestsException::class);
