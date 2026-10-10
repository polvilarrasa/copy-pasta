<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\TrustedPromotionNotification;
use App\Support\UnreadNotificationCount;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

function notificationAged(User $user, int $days, bool $read): string
{
    $user->notify(new TrustedPromotionNotification);
    $notification = $user->notifications()->latest()->first();
    $notification->forceFill([
        'created_at' => now()->subDays($days),
        'read_at' => $read ? now()->subDays($days) : null,
    ])->save();

    return $notification->id;
}

test('borra las leídas de más de 90 días y las no leídas de más de 180, y conserva el resto', function (): void {
    $member = User::factory()->create();
    $keepers = [
        notificationAged($member, 89, read: true),
        notificationAged($member, 179, read: false),
        notificationAged($member, 100, read: false),
    ];
    notificationAged($member, 91, read: true);
    notificationAged($member, 181, read: false);

    Artisan::call('notifications:prune');

    expect($member->notifications()->pluck('id')->all())->toEqualCanonicalizing($keepers);
});

test('al borrar notificaciones sin leer se invalida el contador de la campana', function (): void {
    $member = User::factory()->create();
    notificationAged($member, 200, read: false);
    expect(app(UnreadNotificationCount::class)->get($member))->toBe(1);

    Artisan::call('notifications:prune');

    expect(app(UnreadNotificationCount::class)->get($member))->toBe(0);
});

test('el borrado está programado cada día', function (): void {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains($event->command, 'notifications:prune'));

    expect($events)->toHaveCount(1)->and($events->first()->expression)->toBe('0 0 * * *');
});
