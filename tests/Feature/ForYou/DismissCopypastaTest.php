<?php

declare(strict_types=1);

use App\Enums\EventType;
use App\Livewire\Feed;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\TrackedEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->tag = Tag::factory()->create(['name' => 'humor']);
    $this->member = User::factory()->create();
    $this->copypasta = Copypasta::factory()->create();
    $this->copypasta->tags()->attach($this->tag->id);
});

test('descartar registra el descarte, resta 3 y registra el evento dismiss con su contexto', function (): void {
    $this->actingAs($this->member)
        ->postJson(route('copypastas.dismiss', $this->copypasta), ['source' => 'para_ti', 'position' => 3, 'group' => 'explore'])
        ->assertOk()
        ->assertJson(['dismissed' => true]);

    expect(DB::table('copypasta_dismissals')->where('user_id', $this->member->id)->count())->toBe(1)
        ->and(storedAffinity($this->member, $this->tag))->toBe(-3.0)
        ->and(TrackedEvent::query()->where('type', EventType::Dismiss)->sole()->context)
        ->toEqual(['source' => 'para_ti', 'position' => 3, 'group' => 'explore']);
});

test('deshacer revierte las dos cosas y registra dismiss_undo', function (): void {
    $this->actingAs($this->member);
    $this->postJson(route('copypastas.dismiss', $this->copypasta));

    $this->deleteJson(route('copypastas.dismiss.undo', $this->copypasta))->assertOk()->assertJson(['dismissed' => false]);

    expect(DB::table('copypasta_dismissals')->count())->toBe(0)
        ->and(storedAffinity($this->member, $this->tag))->toBe(0.0)
        ->and(TrackedEvent::query()->where('type', EventType::DismissUndo)->count())->toBe(1);
});

test('descartar dos veces no resta dos veces ni registra dos eventos', function (): void {
    $this->actingAs($this->member);

    $this->postJson(route('copypastas.dismiss', $this->copypasta));
    $this->postJson(route('copypastas.dismiss', $this->copypasta));

    expect(storedAffinity($this->member, $this->tag))->toBe(-3.0)
        ->and(TrackedEvent::query()->where('type', EventType::Dismiss)->count())->toBe(1);
});

test('hace falta sesión', function (): void {
    $this->postJson(route('copypastas.dismiss', $this->copypasta))->assertUnauthorized();
    $this->deleteJson(route('copypastas.dismiss.undo', $this->copypasta))->assertUnauthorized();
});

test('no se puede descartar un copy-pasta propio ni uno oculto', function (): void {
    $this->actingAs($this->member);

    $this->postJson(route('copypastas.dismiss', Copypasta::factory()->for($this->member, 'user')->create()))->assertForbidden();
    $this->postJson(route('copypastas.dismiss', Copypasta::factory()->hidden()->create()))->assertForbidden();
});

test('el copy-pasta descartado desaparece de todos los feeds y vuelve al deshacer', function (): void {
    $this->actingAs($this->member);

    Livewire::test(Feed::class)->set('sort', 'new')->assertSee($this->copypasta->title);

    $this->postJson(route('copypastas.dismiss', $this->copypasta));
    $this->deleteJson(route('copypastas.dismiss.undo', $this->copypasta));

    Livewire::test(Feed::class)->set('sort', 'new')->assertSee($this->copypasta->title);
});

test('la tarjeta ofrece "No me interesa" con sesión y no a anónimos ni en lo propio', function (): void {
    $this->get(route('copypastas.show', [$this->copypasta, $this->copypasta->slug]))
        ->assertDontSee('data-test="card-dismiss"', false);

    $this->actingAs($this->member)->get(route('copypastas.show', [$this->copypasta, $this->copypasta->slug]))
        ->assertSee('data-test="card-dismiss"', false);

    $own = Copypasta::factory()->for($this->member, 'user')->create();
    $this->get(route('copypastas.show', [$own, $own->slug]))->assertDontSee('data-test="card-dismiss"', false);
});
