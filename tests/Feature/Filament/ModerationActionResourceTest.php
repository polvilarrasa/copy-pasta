<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\ModerationActions\Pages\ListModerationActions;
use App\Models\ModerationAction;
use App\Models\User;
use Livewire\Livewire;

test('un moderador ve el log de moderación', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $entry = ModerationAction::factory()->create(['actor_id' => $moderator->id]);

    Livewire::actingAs($moderator)
        ->test(ListModerationActions::class)
        ->assertCanSeeTableRecords([$entry]);
});

test('el log no ofrece crear ni editar entradas', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();

    Livewire::actingAs($moderator)
        ->test(ListModerationActions::class)
        ->assertDontSee('Crear')
        ->assertDontSee('Editar');
});
