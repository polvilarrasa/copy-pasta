<?php

declare(strict_types=1);

use App\Actions\ConcealCopypasta;
use App\Actions\DeleteCopypasta;
use App\Actions\GetFeaturedCopypasta;
use App\Actions\MarkCopypastaNsfw;
use App\Actions\PickFeaturedCopypasta;
use App\Actions\ReplaceFeaturedCopypasta;
use App\Enums\ModerationActionType;
use App\Filament\Admin\Pages\FeaturedCopypastaPage;
use App\Models\Copypasta;
use App\Models\FeaturedCopypasta;
use App\Models\ModerationAction;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(now()->setTime(10, 0));
});

test('elige el de mejor calidad y frescura de las últimas 48 horas, visible, sin NSFW y nunca destacado', function (): void {
    $best = Copypasta::factory()->create(['published_at' => now()->subHours(3), 'score' => 30, 'copies_count' => 10]);
    Copypasta::factory()->create(['published_at' => now()->subHours(30), 'score' => 30]);
    Copypasta::factory()->nsfw()->create(['published_at' => now()->subHours(1), 'score' => 500]);
    Copypasta::factory()->hidden()->create(['published_at' => now()->subHours(1), 'score' => 500]);
    Copypasta::factory()->unpublished()->create(['score' => 500]);
    $featuredBefore = Copypasta::factory()->create(['published_at' => now()->subHours(1), 'score' => 500]);
    DB::table('featured_copypastas')->insert(['date' => now()->subDay()->toDateString(), 'copypasta_id' => $featuredBefore->id, 'created_at' => now(), 'updated_at' => now()]);

    $picked = app(PickFeaturedCopypasta::class)->handle();

    expect($picked->copypasta_id)->toBe($best->id)
        ->and($picked->picked_by_id)->toBeNull()
        ->and($picked->date->toDateString())->toBe(now()->toDateString());
});

test('si no hay ninguno en 48 horas, amplía a 7 días; si tampoco, no hay del día', function (): void {
    $week = Copypasta::factory()->create(['published_at' => now()->subDays(5), 'score' => 9]);
    Copypasta::factory()->create(['published_at' => now()->subDays(9), 'score' => 999]);

    expect(app(PickFeaturedCopypasta::class)->handle()->copypasta_id)->toBe($week->id);

    $this->travelTo(now()->addDay());
    expect(app(PickFeaturedCopypasta::class)->handle())->toBeNull();
    expect(FeaturedCopypasta::query()->where('date', now()->toDateString())->exists())->toBeFalse();
});

test('un copy-pasta no se destaca dos veces y lanzar el comando dos veces el mismo día no cambia nada', function (): void {
    $first = Copypasta::factory()->create(['published_at' => now()->subHours(2), 'score' => 50]);
    $second = Copypasta::factory()->create(['published_at' => now()->subHours(2), 'score' => 10]);

    Artisan::call('featured:pick');
    Artisan::call('featured:pick');

    expect(FeaturedCopypasta::query()->count())->toBe(1)
        ->and(FeaturedCopypasta::query()->sole()->copypasta_id)->toBe($first->id);

    $this->travelTo(now()->addDay());
    Artisan::call('featured:pick');

    expect(FeaturedCopypasta::query()->orderBy('date')->pluck('copypasta_id')->all())->toBe([$first->id, $second->id]);
});

test('el comando está programado a las 00:00 hora de Madrid', function (): void {
    $event = collect(app(Schedule::class)->events())->first(fn ($event): bool => str_contains((string) $event->command, 'featured:pick'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *')
        ->and($event->timezone)->toBe('Europe/Madrid');
});

test('la home lo destaca arriba para todos y no en las otras páginas ni al buscar', function (): void {
    $featured = Copypasta::factory()->create(['title' => 'El táper inmortal', 'published_at' => now()->subHours(2)]);
    app(PickFeaturedCopypasta::class)->handle();
    $this->actingAs(User::factory()->create());

    $this->get(route('home'))->assertSee('El táper inmortal')->assertSee(__('ui.card.featured'));
    $this->get(route('feed.newest'))->assertDontSee(__('ui.card.featured'));

    auth()->logout();
    $this->get(route('home'))->assertSee(__('ui.card.featured'));
    $this->get(route('home', ['q' => 'otra cosa']))->assertDontSee(__('ui.card.featured'));

    expect($featured->refresh()->title)->toBe('El táper inmortal');
});

test('el elegido se guarda en caché hasta medianoche', function (): void {
    $featured = Copypasta::factory()->create(['published_at' => now()->subHours(2)]);
    app(PickFeaturedCopypasta::class)->handle();

    app(GetFeaturedCopypasta::class)->handle(null);
    expect(Cache::get(GetFeaturedCopypasta::cacheKey()))->toBe($featured->id);

    DB::table('featured_copypastas')->delete();
    expect(app(GetFeaturedCopypasta::class)->handle(null)?->id)->toBe($featured->id);

    $this->travelTo(now()->addDay()->startOfDay()->addMinute());
    expect(app(GetFeaturedCopypasta::class)->handle(null))->toBeNull();
});

test('si el elegido se oculta, se borra o se marca NSFW, la home deja de mostrarlo al momento y no se elige otro', function (string $change): void {
    $staff = User::factory()->moderator()->withTwoFactor()->create();
    $featured = Copypasta::factory()->create(['title' => 'El táper inmortal', 'published_at' => now()->subHours(2), 'score' => 50]);
    $other = Copypasta::factory()->create(['title' => 'El sustituto', 'published_at' => now()->subHours(1), 'score' => 5]);
    app(PickFeaturedCopypasta::class)->handle();
    $this->get(route('home'))->assertSee('El táper inmortal');
    expect(Cache::has(GetFeaturedCopypasta::cacheKey()))->toBeTrue();

    match ($change) {
        'hide' => app(ConcealCopypasta::class)->handle($staff, $featured, 'Spam', acceptsPendingReports: true),
        'delete' => app(DeleteCopypasta::class)->handle($featured->user, $featured),
        'nsfw' => app(MarkCopypastaNsfw::class)->handle($staff, $featured, true),
    };

    expect(Cache::has(GetFeaturedCopypasta::cacheKey()))->toBeFalse();
    $this->get(route('home'))->assertDontSee(__('ui.card.featured'));
    expect(app(GetFeaturedCopypasta::class)->handle(null))->toBeNull()
        ->and(FeaturedCopypasta::query()->count())->toBe(1)
        ->and(FeaturedCopypasta::query()->sole()->copypasta_id)->toBe($featured->id)
        ->and($other->refresh()->title)->toBe('El sustituto');
})->with(['hide', 'delete', 'nsfw']);

test('ocultar un copy-pasta que no es el del día no toca la caché', function (): void {
    $staff = User::factory()->moderator()->withTwoFactor()->create();
    Copypasta::factory()->create(['published_at' => now()->subHours(2), 'score' => 50]);
    $other = Copypasta::factory()->create(['published_at' => now()->subHours(1)]);
    app(PickFeaturedCopypasta::class)->handle();
    app(GetFeaturedCopypasta::class)->handle(null);

    app(ConcealCopypasta::class)->handle($staff, $other, 'Spam', acceptsPendingReports: true);

    expect(Cache::has(GetFeaturedCopypasta::cacheKey()))->toBeTrue();
});

test('el staff sustituye el del día: el sustituido sale de la tabla y vuelve a ser elegible', function (): void {
    $staff = User::factory()->moderator()->withTwoFactor()->create();
    $current = Copypasta::factory()->create(['published_at' => now()->subHours(2), 'score' => 50]);
    $replacement = Copypasta::factory()->create(['published_at' => now()->subDays(3), 'score' => 1]);
    app(PickFeaturedCopypasta::class)->handle();
    app(GetFeaturedCopypasta::class)->handle(null);

    app(ReplaceFeaturedCopypasta::class)->handle($staff, $replacement);

    $row = FeaturedCopypasta::query()->sole();
    expect($row->copypasta_id)->toBe($replacement->id)
        ->and($row->picked_by_id)->toBe($staff->id)
        ->and(app(GetFeaturedCopypasta::class)->handle(null)?->id)->toBe($replacement->id)
        ->and(ModerationAction::query()->where('action', ModerationActionType::ReplaceFeatured)->sole()->meta)->toBe(['previous' => $current->id]);

    // El sustituido puede volver a elegirse otro día.
    $this->travelTo(now()->addDay());
    expect(app(PickFeaturedCopypasta::class)->handle()->copypasta_id)->toBe($current->id);
});

test('no se puede sustituir por uno ya destacado, oculto o NSFW, ni por un usuario normal', function (): void {
    $staff = User::factory()->moderator()->withTwoFactor()->create();
    $used = Copypasta::factory()->create(['published_at' => now()->subHours(2)]);
    DB::table('featured_copypastas')->insert(['date' => now()->subDays(2)->toDateString(), 'copypasta_id' => $used->id, 'created_at' => now(), 'updated_at' => now()]);

    expect(fn () => app(ReplaceFeaturedCopypasta::class)->handle($staff, $used))->toThrow(ValidationException::class)
        ->and(fn () => app(ReplaceFeaturedCopypasta::class)->handle($staff, Copypasta::factory()->hidden()->create()))->toThrow(AuthorizationException::class)
        ->and(fn () => app(ReplaceFeaturedCopypasta::class)->handle($staff, Copypasta::factory()->nsfw()->create()))->toThrow(AuthorizationException::class)
        ->and(fn () => app(ReplaceFeaturedCopypasta::class)->handle(User::factory()->create(), Copypasta::factory()->create()))->toThrow(AuthorizationException::class);
});

test('sustituir cuando hoy no hay del día lo establece', function (): void {
    $staff = User::factory()->admin()->withTwoFactor()->create();
    $copypasta = Copypasta::factory()->create();

    app(ReplaceFeaturedCopypasta::class)->handle($staff, $copypasta);

    expect(app(GetFeaturedCopypasta::class)->handle(null)?->id)->toBe($copypasta->id);
});

test('desde /admin el staff ve el actual y lo sustituye', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $staff = User::factory()->moderator()->withTwoFactor()->create();
    $current = Copypasta::factory()->create(['title' => 'El actual', 'published_at' => now()->subHours(2), 'score' => 50]);
    $replacement = Copypasta::factory()->create(['title' => 'El nuevo', 'published_at' => now()->subDays(3)]);
    app(PickFeaturedCopypasta::class)->handle();

    Livewire::actingAs($staff)
        ->test(FeaturedCopypastaPage::class)
        ->assertSee('El actual')
        ->callAction(TestAction::make('replace'), data: ['copypasta' => $replacement->id])
        ->assertHasNoActionErrors()
        ->assertSee('El nuevo');

    expect(FeaturedCopypasta::query()->sole()->copypasta_id)->toBe($replacement->id);
});

test('la página de /admin no es para miembros', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());

    expect(FeaturedCopypastaPage::canAccess())->toBeFalse();

    $this->actingAs(User::factory()->moderator()->withTwoFactor()->create());
    expect(FeaturedCopypastaPage::canAccess())->toBeTrue();
});
