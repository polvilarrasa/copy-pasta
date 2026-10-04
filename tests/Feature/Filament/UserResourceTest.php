<?php

declare(strict_types=1);

use App\Enums\ModerationActionType;
use App\Enums\Role;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\RelationManagers\CopypastasRelationManager;
use App\Filament\Admin\Resources\Users\RelationManagers\ModerationLogRelationManager;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

test('un moderador lista usuarios pero no ve acciones de baneo ni de cambio de rol', function (): void {
    $moderator = User::factory()->moderator()->create();
    $member = User::factory()->create();

    Livewire::actingAs($moderator)
        ->test(ListUsers::class)
        ->assertCanSeeTableRecords([$member])
        ->assertTableActionHidden('ban', $member)
        ->assertTableActionHidden('changeRole', $member)
        ->assertTableActionHidden('impersonate', $member);
});

test('un moderador no abre la edición de un usuario', function (): void {
    $this->actingAs(User::factory()->moderator()->create())
        ->get(UserResource::getUrl('edit', ['record' => User::factory()->create()]))
        ->assertForbidden();
});

test('un admin no ve el baneo de sí mismo', function (): void {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->assertTableActionHidden('ban', $admin);
});

test('un admin banea a un miembro desde la tabla con motivo', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->callAction(TestAction::make('ban')->table($member), data: ['reason' => 'Spam repetido'])
        ->assertHasNoActionErrors();

    expect($member->refresh()->isBanned())->toBeTrue();
});

test('un admin cambia el rol de un miembro desde la tabla', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->callAction(TestAction::make('changeRole')->table($member), data: ['role' => Role::Moderator->value])
        ->assertHasNoActionErrors();

    expect($member->refresh()->role)->toBe(Role::Moderator);
});

test('la vista del usuario tiene pestañas con sus copy-pastas, reportes y log de moderación', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->for($member, 'user')->create();
    $entry = ModerationAction::query()->create([
        'actor_id' => $admin->id,
        'action' => ModerationActionType::Ban,
        'subject_type' => User::class,
        'subject_id' => $member->id,
        'reason' => 'Motivo',
    ]);

    Livewire::actingAs($admin)
        ->test(CopypastasRelationManager::class, ['ownerRecord' => $member, 'pageClass' => ViewUser::class])
        ->assertCanSeeTableRecords([$copypasta]);

    Livewire::actingAs($admin)
        ->test(ModerationLogRelationManager::class, ['ownerRecord' => $member, 'pageClass' => ViewUser::class])
        ->assertCanSeeTableRecords([$entry]);
});
