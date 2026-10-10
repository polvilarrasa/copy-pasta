<?php

declare(strict_types=1);

use App\Actions\AdjustAchievementProgress;
use App\Actions\EvaluateUserAchievements;
use App\Actions\RestoreAchievement;
use App\Actions\RevokeAchievement;
use App\Enums\Achievement;
use App\Enums\AchievementMetric;
use App\Enums\ModerationActionType;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\RelationManagers\AchievementsRelationManager;
use App\Models\ModerationAction;
use App\Models\User;
use App\Models\UserAchievement;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

test('un admin revoca un logro con motivo y queda en moderation_actions', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->create();
    $row = UserAchievement::factory()->for($member)->ofAchievement(Achievement::Viral)->create();

    app(RevokeAchievement::class)->handle($admin, $row, '  Copias de una granja  ');

    expect($row->refresh())
        ->revoked_at->not->toBeNull()
        ->revoked_by_id->toBe($admin->id)
        ->revoke_reason->toBe('Copias de una granja')
        ->and(ModerationAction::query()->where('action', ModerationActionType::RevokeAchievement)->sole())
        ->actor_id->toBe($admin->id)
        ->subject_id->toBe((string) $member->id)
        ->reason->toBe('Copias de una granja')
        ->meta->toBe(['achievement' => 'viral']);
});

test('revocar o restaurar exige un motivo', function (string $reason): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $row = UserAchievement::factory()->create();

    expect(fn () => app(RevokeAchievement::class)->handle($admin, $row, $reason))->toThrow(ValidationException::class);

    $row->forceFill(['revoked_at' => now()])->save();

    expect(fn () => app(RestoreAchievement::class)->handle($admin, $row, $reason))->toThrow(ValidationException::class);
    expect(ModerationAction::query()->count())->toBe(0);
})->with(['vacío' => [''], 'solo espacios' => ['   ']]);

test('solo un admin revoca o restaura', function (User $actor): void {
    $row = UserAchievement::factory()->create();

    app(RevokeAchievement::class)->handle($actor, $row, 'Motivo');
})->with([
    'un moderador' => fn (): User => User::factory()->moderator()->withTwoFactor()->create(),
    'un miembro' => fn (): User => User::factory()->create(),
])->throws(AuthorizationException::class);

test('un logro revocado no vuelve a concederse al cumplir de nuevo la condición', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->create();
    app(AdjustAchievementProgress::class)->add($member, AchievementMetric::Published, 1);
    app(EvaluateUserAchievements::class)->handle($member, [AchievementMetric::Published]);
    app(RevokeAchievement::class)->handle($admin, $member->achievements()->sole(), 'Motivo');
    $member->notifications()->delete();

    app(AdjustAchievementProgress::class)->add($member, AchievementMetric::Published, 5);
    $granted = app(EvaluateUserAchievements::class)->handle($member, [AchievementMetric::Published]);

    expect($granted)->toBe([])
        ->and(holdsAchievement($member, Achievement::FirstPaste))->toBeFalse()
        ->and($member->achievements()->count())->toBe(1)
        ->and($member->notifications()->count())->toBe(0);
});

test('restaurar devuelve el logro sin notificar ni volver a fijar el título', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->create();
    $row = UserAchievement::factory()->for($member)->ofAchievement(Achievement::Viral)->revoked()->create();

    app(RestoreAchievement::class)->handle($admin, $row, 'Fue un error');

    expect($row->refresh()->revoked_at)->toBeNull()
        ->and($row->revoke_reason)->toBeNull()
        ->and(holdsAchievement($member, Achievement::Viral))->toBeTrue()
        ->and($member->refresh()->title_key)->toBeNull()
        ->and($member->notifications()->count())->toBe(0)
        ->and(ModerationAction::query()->where('action', ModerationActionType::RestoreAchievement)->sole()->reason)->toBe('Fue un error');
});

test('desde la ficha de usuario un admin revoca y restaura con motivo', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->create();
    $row = UserAchievement::factory()->for($member)->ofAchievement(Achievement::Viral)->create();

    $component = Livewire::actingAs($admin)
        ->test(AchievementsRelationManager::class, ['ownerRecord' => $member, 'pageClass' => ViewUser::class])
        ->assertSee('Viral')
        ->callAction(TestAction::make('revoke')->table($row), data: ['reason' => 'Trampa'])
        ->assertHasNoActionErrors();

    expect($row->refresh()->isRevoked())->toBeTrue();

    $component
        ->callAction(TestAction::make('restore')->table($row), data: ['reason' => 'Perdón'])
        ->assertHasNoActionErrors();

    expect($row->refresh()->isRevoked())->toBeFalse();
});

test('desde la ficha el motivo es obligatorio', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->create();
    $row = UserAchievement::factory()->for($member)->create();

    Livewire::actingAs($admin)
        ->test(AchievementsRelationManager::class, ['ownerRecord' => $member, 'pageClass' => ViewUser::class])
        ->callAction(TestAction::make('revoke')->table($row), data: ['reason' => ''])
        ->assertHasActionErrors(['reason' => 'required']);

    expect($row->refresh()->isRevoked())->toBeFalse();
});

test('la pestaña de logros no se ofrece a un moderador', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $member = User::factory()->create();

    $this->actingAs($moderator);

    expect(AchievementsRelationManager::canViewForRecord($member, ViewUser::class))->toBeFalse();
});
