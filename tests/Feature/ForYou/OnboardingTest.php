<?php

declare(strict_types=1);

use App\Actions\ImpersonateUser;
use App\Actions\UpdateFavoriteTags;
use App\Enums\EventType;
use App\Livewire\Welcome;
use App\Models\Tag;
use App\Models\TrackedEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('tras el registro se redirige a /bienvenida y queda marcada como ofrecida', function (): void {
    $this->post(route('register.store'), [
        'username' => 'recien_llegado',
        'email' => 'nuevo@example.com',
        'password' => 'Correct-Horse-Battery-9',
        'password_confirmation' => 'Correct-Horse-Battery-9',
    ])->assertRedirect(route('welcome', absolute: false));

    expect(User::query()->where('username', 'recien_llegado')->sole()->onboarded_at)->not->toBeNull();
});

test('un usuario existente sin onboarded_at va a /bienvenida al entrar en el feed, una sola vez', function (): void {
    $member = User::factory()->notOnboarded()->create();

    $this->actingAs($member)->get(route('home'))->assertRedirect(route('welcome'));
    expect($member->refresh()->onboarded_at)->not->toBeNull();

    $this->get(route('home'))->assertOk();
    $this->get(route('feed.top-week'))->assertOk();
});

test('lo mismo en cualquiera de las páginas del feed', function (string $uri): void {
    $member = User::factory()->notOnboarded()->create();

    $this->actingAs($member)->get($uri)->assertRedirect(route('welcome'));
})->with(['/', '/nuevos', '/top', '/top/semana', '/top/mes']);

test('no hay redirección para el staff, ni en /admin, ni para anónimos', function (): void {
    $moderator = User::factory()->moderator()->notOnboarded()->withTwoFactor()->create();

    $this->actingAs($moderator)->get(route('home'))->assertOk();
    expect($moderator->refresh()->onboarded_at)->toBeNull();

    $this->actingAs(User::factory()->admin()->notOnboarded()->withTwoFactor()->create());
    expect((string) $this->get('/admin')->headers->get('Location'))->not->toContain('bienvenida');

    auth()->logout();
    $this->get(route('home'))->assertOk();
});

test('no hay redirección durante una impersonación y no se marca al usuario', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->notOnboarded()->create();

    $this->actingAs($admin);
    app(ImpersonateUser::class)->handle($admin, $member);
    expect(is_impersonating())->toBeTrue();

    $this->get(route('home'))->assertOk();
    expect($member->refresh()->onboarded_at)->toBeNull();
});

test('solo las visitas GET redirigen', function (): void {
    $member = User::factory()->notOnboarded()->create();

    $this->actingAs($member)->post(route('theme.update'), ['theme' => 'dark'])->assertSessionMissing('errors');
    expect($member->refresh()->onboarded_at)->toBeNull();
});

test('/bienvenida exige sesión', function (): void {
    $this->get(route('welcome'))->assertRedirect(route('login'));
});

test('hacen falta al menos 3 etiquetas para guardar, y se guardan las favoritas', function (): void {
    [$a, $b, $c, $d] = Tag::factory()->count(4)->create();
    $member = User::factory()->notOnboarded()->create();

    Livewire::actingAs($member)->test(Welcome::class)
        ->call('toggle', $a->id)
        ->call('toggle', $b->id)
        ->call('save')
        ->assertHasErrors('tags');

    expect(DB::table('user_favorite_tags')->count())->toBe(0);

    Livewire::actingAs($member)->test(Welcome::class)
        ->call('toggle', $a->id)
        ->call('toggle', $b->id)
        ->call('toggle', $c->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    expect(DB::table('user_favorite_tags')->where('user_id', $member->id)->pluck('tag_id')->sort()->values()->all())
        ->toBe([$a->id, $b->id, $c->id])
        ->and($member->refresh()->onboarded_at)->not->toBeNull()
        ->and($d->id)->not->toBeIn(DB::table('user_favorite_tags')->pluck('tag_id')->all());
});

test('el botón enseña cuántas faltan y se habilita con 3', function (): void {
    $tags = Tag::factory()->count(3)->create();

    $component = Livewire::actingAs(User::factory()->create())->test(Welcome::class)
        ->assertSee('Elige 3 más')
        ->call('toggle', $tags[0]->id)
        ->assertSee('Elige 2 más')
        ->call('toggle', $tags[1]->id)
        ->assertSee('Elige 1 más')
        ->call('toggle', $tags[2]->id)
        ->assertSee(__('public.welcome.see_feed'));

    $component->call('toggle', $tags[2]->id)->assertSee('Elige 1 más');
});

test('"Saltar por ahora" marca onboarded_at sin guardar etiquetas', function (): void {
    Tag::factory()->count(3)->create();
    $member = User::factory()->notOnboarded()->create();

    Livewire::actingAs($member)->test(Welcome::class)->call('skip')->assertRedirect(route('home'));

    expect($member->refresh()->onboarded_at)->not->toBeNull()
        ->and(DB::table('user_favorite_tags')->count())->toBe(0)
        ->and(TrackedEvent::query()->where('type', EventType::FavoriteTagsUpdate)->sole()->context)->toBe(['skipped' => true]);
});

test('guardar registra favorite_tags_update con las etiquetas', function (): void {
    $tags = Tag::factory()->count(3)->create();
    $member = User::factory()->create();

    app(UpdateFavoriteTags::class)->handle($member, $tags->pluck('id')->all());

    expect(TrackedEvent::query()->where('type', EventType::FavoriteTagsUpdate)->sole()->context)
        ->toBe(['tag_ids' => $tags->pluck('id')->all()]);
});

test('las etiquetas inactivas o inexistentes no cuentan para el mínimo', function (): void {
    $active = Tag::factory()->count(2)->create();
    $inactive = Tag::factory()->create(['is_active' => false]);

    expect(fn () => app(UpdateFavoriteTags::class)->handle(User::factory()->create(), [...$active->pluck('id')->all(), $inactive->id, 9999]))
        ->toThrow(ValidationException::class);
});

test('editar las favoritas es añadir y quitar filas, sin tocar las puntuaciones', function (): void {
    [$a, $b, $c, $d] = Tag::factory()->count(4)->create();
    $member = User::factory()->create();
    DB::table('user_tag_affinities')->insert(['user_id' => $member->id, 'tag_id' => $a->id, 'score' => 7.0, 'updated_at' => now()]);
    app(UpdateFavoriteTags::class)->handle($member, [$a->id, $b->id, $c->id]);

    app(UpdateFavoriteTags::class)->handle($member, [$b->id, $c->id, $d->id]);

    expect(DB::table('user_favorite_tags')->where('user_id', $member->id)->pluck('tag_id')->sort()->values()->all())->toBe([$b->id, $c->id, $d->id])
        ->and(storedAffinity($member, $a))->toBe(7.0)
        ->and(DB::table('user_tag_affinities')->count())->toBe(1);
});

test('/bienvenida?modo=editar edita las favoritas, parte de las actuales y no ofrece saltar', function (): void {
    $tags = Tag::factory()->count(3)->create();
    $member = User::factory()->create();
    app(UpdateFavoriteTags::class)->handle($member, $tags->pluck('id')->all());

    $this->actingAs($member)->get(route('welcome', ['modo' => 'editar']))
        ->assertOk()
        ->assertSee(__('public.welcome.edit_title'))
        ->assertSee('3 elegidas')
        ->assertDontSee(__('public.welcome.skip'));

    $this->get(route('welcome'))->assertOk()->assertSee(__('public.welcome.skip'));
});

test('la barra lateral del feed y los ajustes llevan a editar las favoritas', function (): void {
    $member = User::factory()->create();
    $tag = Tag::factory()->create(['name' => 'humor']);
    DB::table('user_favorite_tags')->insert(['user_id' => $member->id, 'tag_id' => $tag->id, 'created_at' => now()]);

    $this->actingAs($member)->get(route('home'))
        ->assertSee('data-test="favorite-tags"', false)
        ->assertSee('#humor')
        ->assertSee(route('welcome', ['modo' => 'editar']), false);

    $this->get(route('notifications.edit'))->assertSee('data-test="settings-favorite-tags"', false);
});

test('la barra lateral no aparece para anónimos', function (): void {
    $this->get(route('home'))->assertDontSee('data-test="favorite-tags"', false);
});
