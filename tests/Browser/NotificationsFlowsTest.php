<?php

declare(strict_types=1);

use App\Enums\MilestoneMetric;
use App\Models\Copypasta;
use App\Models\User;
use App\Notifications\CopypastaMilestoneNotification;
use App\Notifications\TrustedPromotionNotification;

test('la campana muestra el contador, abre el desplegable, lleva al destino y el contador baja', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->for($member, 'user')->create(['title' => 'Aviso del táper']);
    $member->notify(new CopypastaMilestoneNotification($copypasta, MilestoneMetric::Upvotes, 10));
    $member->notify(new TrustedPromotionNotification);

    signInInBrowser($member);

    $page = visitInteractive('/');

    $page->assertSeeIn('@notification-count', '2')
        ->click('@notification-bell')
        ->assertSee('«Aviso del táper» ha llegado a 10 upvotes.')
        ->click('«Aviso del táper» ha llegado a 10 upvotes.')
        ->assertPathIs('/c/'.$copypasta->getKey().'/'.$copypasta->slug)
        ->assertSeeIn('@notification-count', '1');

    expect($member->unreadNotifications()->count())->toBe(1);
});

test('"Ver todas" lleva a /notificaciones y "Marcar todas como leídas" deja la campana sin contador', function (): void {
    $member = User::factory()->create();
    $member->notify(new TrustedPromotionNotification);

    signInInBrowser($member);

    visitInteractive('/')
        ->click('@notification-bell')
        ->click(__('notifications.bell.view_all'))
        ->assertPathIs('/notificaciones')
        ->assertScript(interactivePageScript())
        ->assertSee(__('notifications.trusted_promotion'))
        ->press('@notifications-mark-all')
        ->assertMissing('@notification-count');
});
