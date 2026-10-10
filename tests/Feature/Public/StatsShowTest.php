<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('un invitado que visita /estadisticas se redirige a iniciar sesión', function (): void {
    $this->get('/estadisticas')->assertRedirect('/login');
});

test('un miembro ve sus propias estadísticas', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->for($user, 'user')->create();

    DB::table('copypasta_daily_stats')->insert([
        'copypasta_id' => $copypasta->getKey(),
        'date' => now()->subDay()->toDateString(),
        'copies' => 10,
    ]);

    $this->actingAs($user)
        ->get('/estadisticas')
        ->assertOk()
        ->assertSee(__('public.stats.title'))
        ->assertSee(__('public.stats.kpi.copies'));
});

test('/app redirige a /estadisticas', function (): void {
    $this->get('/app')->assertRedirect('/estadisticas');
});
