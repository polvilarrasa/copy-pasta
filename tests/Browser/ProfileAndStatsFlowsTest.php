<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('el perfil público lista las copy-pastas del usuario y cambia de pestaña', function (): void {
    $user = User::factory()->create(['username' => 'browser_profile']);
    $popular = Copypasta::factory()->for($user, 'user')->publishedDaysAgo(5)->create(['title' => 'El mas popular', 'score' => 90]);
    $recent = Copypasta::factory()->for($user, 'user')->publishedDaysAgo(1)->create(['title' => 'El mas reciente', 'score' => 1]);

    visit('/u/browser_profile')
        ->assertSee('browser_profile')
        ->assertSee($popular->title)
        ->click('Nuevos')
        ->assertSee($recent->title);
});

test('abre /estadisticas, cambia de métrica y la tabla accesible cambia con ella', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->for($user, 'user')->create();

    DB::table('copypasta_daily_stats')->insert([
        'copypasta_id' => $copypasta->getKey(),
        'date' => now()->subDay()->toDateString(),
        'copies' => 17,
        'upvotes' => 6,
        'downvotes' => 1,
    ]);

    signInInBrowser($user);

    $page = visit('/estadisticas')
        ->assertSee(__('public.stats.title'))
        ->assertSourceHas('Copias por día');

    $page->click('Votos netos')
        ->assertSourceHas('Votos netos por día')
        ->assertSourceMissing('Copias por día');
});
