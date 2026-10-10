<?php

declare(strict_types=1);

use App\Actions\ImpersonateUser;
use App\Enums\MilestoneMetric;
use App\Livewire\NotificationBell;
use App\Livewire\NotificationsList;
use App\Models\Copypasta;
use App\Models\User;
use App\Notifications\CopypastaHiddenNotification;
use App\Notifications\CopypastaMilestoneNotification;
use App\Notifications\CopypastaRestoredNotification;
use App\Notifications\ReportAcceptedNotification;
use App\Notifications\TrustedPromotionNotification;
use App\Support\NotificationPresenter;
use Livewire\Livewire;

beforeEach(fn () => prepareEventPartitions());

test('la campana solo aparece para miembros con sesión y muestra el contador', function (): void {
    $this->get('/')->assertDontSee('data-test="notification-bell"', false);

    $member = User::factory()->create();
    $member->notify(new TrustedPromotionNotification);
    $member->notify(new TrustedPromotionNotification);

    $this->actingAs($member)->get('/')
        ->assertSee('data-test="notification-bell"', false)
        ->assertSee('Notificaciones, 2 sin leer');
});

test('el desplegable no consulta la lista hasta que se abre', function (): void {
    $member = User::factory()->create();
    $member->notify(new TrustedPromotionNotification);

    Livewire::actingAs($member)->test(NotificationBell::class)
        ->assertSee(__('notifications.bell.loading'))
        ->assertDontSee(__('notifications.trusted_promotion'))
        ->call('loadList')
        ->assertSee(__('notifications.trusted_promotion'));
});

test('el texto sale de lang al mostrarla, con el hito agrupado y los miles con punto', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->for($member, 'user')->create(['title' => 'El táper']);
    $member->notify(new CopypastaMilestoneNotification($copypasta, MilestoneMetric::Copies, 1000));

    Livewire::actingAs($member)->test(NotificationBell::class)
        ->call('loadList')
        ->assertSee('«El táper» ha llegado a 1.000 copias.');
});

test('una notificación cuyo destino está oculto, borrado o no existe muestra Contenido retirado sin enlace', function (string $state): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->for($member, 'user')->create(['title' => 'Título secreto']);
    $member->notify(new CopypastaMilestoneNotification($copypasta, MilestoneMetric::Upvotes, 10));

    match ($state) {
        'oculto' => $copypasta->forceFill(['hidden_at' => now()])->save(),
        'borrado' => $copypasta->delete(),
        'inexistente' => $copypasta->forceDelete(),
    };

    Livewire::actingAs($member)->test(NotificationBell::class)
        ->call('loadList')
        ->assertSee(__('notifications.removed'))
        ->assertDontSee('Título secreto');

    expect(app(NotificationPresenter::class)->present($member->notifications()->get())->sole()->url)->toBeNull();
})->with(['oculto', 'borrado', 'inexistente']);

test('la notificación de oculto enlaza al detalle mientras el copy-pasta no esté borrado', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->for($member, 'user')->hidden()->create(['title' => 'Mi texto']);
    $member->notify(new CopypastaHiddenNotification($copypasta));

    $item = app(NotificationPresenter::class)->present($member->notifications()->get())->sole();

    expect($item->url)->toBe(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->and($item->text)->toContain('Mi texto');

    $copypasta->delete();

    $item = app(NotificationPresenter::class)->present($member->notifications()->get())->sole();

    expect($item->url)->toBeNull()->and($item->text)->toBe(__('notifications.removed'));
});

test('la notificación de restaurado sin destino visible, y la de reporte aceptado, no llevan enlace', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->for($member, 'user')->hidden()->create();
    $member->notify(new CopypastaRestoredNotification($copypasta));
    $member->notify(new ReportAcceptedNotification($copypasta));

    $items = app(NotificationPresenter::class)->present($member->notifications()->get());

    expect($items->pluck('url')->all())->toBe([null, null])
        ->and($items->pluck('text')->all())->toContain(__('notifications.report_accepted'));
});

test('pulsar una notificación la marca como leída, baja el contador y redirige al destino', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->for($member, 'user')->create();
    $member->notify(new CopypastaMilestoneNotification($copypasta, MilestoneMetric::Copies, 10));
    $id = $member->notifications()->sole()->id;

    Livewire::actingAs($member)->test(NotificationBell::class)
        ->call('open', $id)
        ->assertRedirect(route('copypastas.show', [$copypasta, $copypasta->slug]));

    expect($member->unreadNotifications()->count())->toBe(0);
});

test('pulsar una notificación sin destino la marca como leída y no redirige', function (): void {
    $member = User::factory()->create();
    $member->notify(new ReportAcceptedNotification(Copypasta::factory()->create()));

    Livewire::actingAs($member)->test(NotificationBell::class)
        ->call('open', $member->notifications()->sole()->id)
        ->assertNoRedirect();

    expect($member->unreadNotifications()->count())->toBe(0);
});

test('la pestaña Sin leer solo lista las no leídas', function (): void {
    $member = User::factory()->create();
    $member->notify(new TrustedPromotionNotification);
    $member->notify(new ReportAcceptedNotification(Copypasta::factory()->create()));
    $member->notifications()->where('type', 'trusted_promotion')->update(['read_at' => now()]);

    Livewire::actingAs($member)->test(NotificationBell::class)
        ->call('loadList')
        ->call('setTab', 'unread')
        ->assertSee(__('notifications.report_accepted'))
        ->assertDontSee(__('notifications.trusted_promotion'));
});

test('"Marcar leídas" del desplegable marca todas', function (): void {
    $member = User::factory()->create();
    $member->notify(new TrustedPromotionNotification);
    $member->notify(new TrustedPromotionNotification);

    Livewire::actingAs($member)->test(NotificationBell::class)->call('markAllRead');

    expect($member->unreadNotifications()->count())->toBe(0);
});

test('/notificaciones exige sesión, pagina y marca todas como leídas', function (): void {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));

    $member = User::factory()->create();
    foreach (range(1, NotificationsList::PER_PAGE + 3) as $_) {
        $member->notify(new TrustedPromotionNotification);
    }

    $component = Livewire::actingAs($member)->test(NotificationsList::class);

    expect($component->viewData('items'))->toHaveCount(NotificationsList::PER_PAGE);

    $component->call('nextPage');
    expect($component->viewData('items'))->toHaveCount(3);

    $component->call('markAllRead');
    expect($member->unreadNotifications()->count())->toBe(0);
});

test('durante una impersonación no se ofrece Marcar leídas y las notificaciones siguen sin leer', function (): void {
    $member = User::factory()->create();
    $member->notify(new TrustedPromotionNotification);
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    app(ImpersonateUser::class)->handle($admin, $member);

    Livewire::test(NotificationBell::class)
        ->call('loadList')
        ->assertDontSee(__('notifications.bell.mark_all'))
        ->call('markAllRead');

    $this->get(route('notifications.index'))->assertOk()->assertDontSee(__('notifications.page.mark_all'));

    expect($member->unreadNotifications()->count())->toBe(1);
});
