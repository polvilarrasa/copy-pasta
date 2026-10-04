<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\Copypastas\Pages\ListCopypastas;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

test('un moderador ve la lista de copy-pastas con los ocultos incluidos', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $visible = Copypasta::factory()->create();
    $hidden = Copypasta::factory()->hidden()->create();

    Livewire::actingAs($moderator)
        ->test(ListCopypastas::class)
        ->assertCanSeeTableRecords([$visible, $hidden]);
});

test('un moderador oculta un copy-pasta desde la tabla con un motivo', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $copypasta = Copypasta::factory()->create();

    Livewire::actingAs($moderator)
        ->test(ListCopypastas::class)
        ->callAction(TestAction::make('hide')->table($copypasta), data: ['reason' => 'Contenido ofensivo'])
        ->assertHasNoActionErrors();

    expect($copypasta->refresh()->isHidden())->toBeTrue();

    $this->assertDatabaseHas('moderation_actions', [
        'actor_id' => $moderator->id,
        'subject_id' => $copypasta->id,
        'reason' => 'Contenido ofensivo',
    ]);
});

test('ocultar sin motivo muestra error de validación y no oculta nada', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $copypasta = Copypasta::factory()->create();

    Livewire::actingAs($moderator)
        ->test(ListCopypastas::class)
        ->callAction(TestAction::make('hide')->table($copypasta), data: ['reason' => ''])
        ->assertHasActionErrors(['reason' => 'required']);

    expect($copypasta->refresh()->isHidden())->toBeFalse()
        ->and(ModerationAction::query()->count())->toBe(0);
});

test('un moderador restaura un copy-pasta oculto desde la tabla', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $copypasta = Copypasta::factory()->hidden()->create();

    Livewire::actingAs($moderator)
        ->test(ListCopypastas::class)
        ->callAction(TestAction::make('restore')->table($copypasta))
        ->assertHasNoActionErrors();

    expect($copypasta->refresh()->isHidden())->toBeFalse();

    $this->assertDatabaseHas('moderation_actions', [
        'actor_id' => $moderator->id,
        'action' => 'restore',
        'subject_id' => $copypasta->id,
    ]);
});

test('un moderador marca un copy-pasta como NSFW desde la tabla', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $copypasta = Copypasta::factory()->create(['is_nsfw' => false]);

    Livewire::actingAs($moderator)
        ->test(ListCopypastas::class)
        ->callAction(TestAction::make('toggleNsfw')->table($copypasta))
        ->assertHasNoActionErrors();

    expect($copypasta->refresh()->is_nsfw)->toBeTrue();
});
