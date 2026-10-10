<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('registrarse, elegir 3 etiquetas, ver Para ti con explicaciones y descartar con deshacer', function (): void {
    [$humor, $chat, $gaming] = [
        Tag::factory()->create(['name' => 'humor', 'slug' => 'humor']),
        Tag::factory()->create(['name' => 'chat', 'slug' => 'chat']),
        Tag::factory()->create(['name' => 'gaming', 'slug' => 'gaming']),
    ];
    $liked = Copypasta::factory()->create(['title' => 'Aviso del táper', 'published_at' => now()->subDays(4)]);
    $liked->tags()->attach($humor->id);

    visitInteractive('/register')
        ->fill('username', 'recien_llegado')
        ->fill('email', 'recien@example.com')
        ->fill('password', 'Correct-Horse-Battery-9')
        ->fill('password_confirmation', 'Correct-Horse-Battery-9')
        ->press('@register-user-button')
        ->assertPathIs('/bienvenida')
        ->assertScript(interactivePageScript())
        ->assertSee(__('public.welcome.title'))
        ->assertSeeIn('@welcome-count', 'Ninguna elegida')
        ->click('#welcome-tag-humor')
        ->click('#welcome-tag-chat')
        ->assertSee('Elige 1 más')
        ->click('#welcome-tag-gaming')
        ->assertSeeIn('@welcome-count', '3 elegidas')
        ->press('@welcome-save')
        ->assertPathIs('/')
        ->assertScript(interactivePageScript())
        ->assertSee(__('public.feed.for_you_refresh'))
        ->assertSee('Aviso del táper')
        ->assertSee(__('ui.card.reason'))
        ->assertSee('#humor')
        ->assertSeeIn('@favorite-tags', '#gaming')
        ->click(__('ui.card.more'))
        ->click(__('public.dismiss.button'))
        ->assertDontSee('Aviso del táper')
        ->assertSee(__('public.dismiss.undo'))
        ->click(__('public.dismiss.undo'))
        ->assertSee('Aviso del táper');

    $member = User::query()->where('username', 'recien_llegado')->sole();

    expect(DB::table('user_favorite_tags')->where('user_id', $member->id)->count())->toBe(3);

    // The card comes back on screen at once; the request that reverts the dismissal may still be in flight.
    eventually(function () use ($member, $humor): void {
        expect(DB::table('copypasta_dismissals')->where('user_id', $member->id)->count())->toBe(0)
            ->and(storedAffinity($member, $humor))->toEqualWithDelta(0.0, 0.001);
    });
});

test('una cuenta sin onboarded_at pasa por /bienvenida al iniciar sesión, puede saltarla y no vuelve', function (): void {
    $member = User::factory()->notOnboarded()->create();
    Tag::factory()->count(3)->create();

    // El inicio de sesión termina en el feed, que es lo que ofrece la bienvenida.
    signInInBrowser($member);

    visitInteractive('/bienvenida')
        ->press('@welcome-skip')
        ->assertPathIs('/');

    visit('/')->assertPathIs('/');

    expect($member->refresh()->onboarded_at)->not->toBeNull()
        ->and(DB::table('user_favorite_tags')->count())->toBe(0);
});

test('"Editar favoritas" desde el feed abre la pantalla en modo edición', function (): void {
    $member = User::factory()->create();
    $tags = Tag::factory()->count(3)->create();
    DB::table('user_favorite_tags')->insert($tags->map(fn (Tag $tag): array => ['user_id' => $member->id, 'tag_id' => $tag->id, 'created_at' => now()])->all());

    signInInBrowser($member);

    visitInteractive('/')
        ->click('@edit-favorites')
        ->assertPathIs('/bienvenida')
        ->assertSee(__('public.welcome.edit_title'))
        ->assertDontSee(__('public.welcome.skip'));
});
