<?php

declare(strict_types=1);

use App\Livewire\ProfileCopypastas;
use App\Models\Copypasta;
use App\Models\User;
use App\Models\UsernameHistory;
use Livewire\Livewire;

/**
 * The value shown by a `data-test="..."` span, read straight from the response's raw HTML, so a number appearing
 * elsewhere on the page (a csrf token, a count of something else) cannot make the assertion pass by accident.
 */
function counterValue(string $content, string $dataTest): string
{
    preg_match('/data-test="'.preg_quote($dataTest, '/').'"[^>]*>([^<]*)</', $content, $matches);

    return trim($matches[1] ?? '');
}

test('los contadores del perfil solo cuentan copy-pastas visibles', function (): void {
    $user = User::factory()->create(['username' => 'paco_nocturno']);
    Copypasta::factory()->for($user)->create(['copies_count' => 10, 'upvotes_count' => 4]);
    Copypasta::factory()->for($user)->hidden()->create(['copies_count' => 100, 'upvotes_count' => 100]);
    Copypasta::factory()->for($user)->unpublished()->create(['copies_count' => 100, 'upvotes_count' => 100]);
    $deleted = Copypasta::factory()->for($user)->create(['copies_count' => 100, 'upvotes_count' => 100]);
    $deleted->delete();

    $content = $this->get('/u/paco_nocturno')->assertOk()->assertSee('paco_nocturno')->getContent();

    expect(counterValue($content, 'profile-counter-published'))->toBe('1')
        ->and(counterValue($content, 'profile-counter-copies'))->toBe('10')
        ->and(counterValue($content, 'profile-counter-upvotes'))->toBe('4');
});

test('un username antiguo redirige con 301 al actual', function (): void {
    $user = User::factory()->create(['username' => 'nombre_nuevo']);
    UsernameHistory::query()->create(['user_id' => $user->getKey(), 'username' => 'nombre_viejo', 'changed_at' => now()->subDays(10)]);

    $this->get('/u/nombre_viejo')
        ->assertRedirect('/u/nombre_nuevo')
        ->assertStatus(301);
});

test('un username que nadie tiene responde 404', function (): void {
    $this->get('/u/nadie-tiene-este-nombre')->assertNotFound();
});

test('el perfil de una cuenta anonimizada responde 404', function (): void {
    $user = User::factory()->create(['username' => 'se_borro']);
    $user->forceFill(['deleted_at' => now(), 'anonymized_at' => now()])->save();

    $this->get('/u/se_borro')->assertNotFound();
});

test('el perfil de una cuenta baneada responde 404', function (): void {
    $user = User::factory()->banned()->create(['username' => 'usuario_baneado']);

    $this->get('/u/usuario_baneado')->assertNotFound();
});

test('la pestaña top ordena por score y la de nuevos por fecha de publicación', function (): void {
    $user = User::factory()->create();
    $popular = Copypasta::factory()->for($user)->publishedDaysAgo(5)->create(['title' => 'El mas votado', 'score' => 50]);
    $recent = Copypasta::factory()->for($user)->publishedDaysAgo(1)->create(['title' => 'El mas reciente', 'score' => 1]);

    Livewire::test(ProfileCopypastas::class, ['user' => $user])
        ->assertSeeInOrder([$popular->title, $recent->title])
        ->call('selectTab', 'new')
        ->assertSeeInOrder([$recent->title, $popular->title]);
});

test('la lista del perfil no muestra copy-pastas ocultos ni borrados', function (): void {
    $user = User::factory()->create();
    $visible = Copypasta::factory()->for($user)->create(['title' => 'Visible de verdad']);
    Copypasta::factory()->for($user)->hidden()->create(['title' => 'Oculto por moderación']);

    Livewire::test(ProfileCopypastas::class, ['user' => $user])
        ->assertSee($visible->title)
        ->assertDontSee('Oculto por moderación');
});
