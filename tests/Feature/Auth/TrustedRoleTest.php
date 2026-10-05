<?php

declare(strict_types=1);

use App\Actions\ChangeUserRole;
use App\Enums\ModerationActionType;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Filament\Admin\Widgets\TrustCandidatesWidget;
use App\Models\ModerationAction;
use App\Models\Report;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

test('un admin asigna el rol de confianza y el cambio queda registrado', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->established()->create();

    app(ChangeUserRole::class)->handle($admin, $member, Role::Trusted);

    expect($member->refresh()->role)->toBe(Role::Trusted)
        ->and(ModerationAction::query()->where('action', ModerationActionType::ChangeRole)->sole()->subject_id)->toEqual($member->getKey());
});

test('un usuario de confianza no entra al panel de administración', function (): void {
    $trusted = User::factory()->trusted()->create();

    expect($trusted->canAccessPanel(Panel::make()->id('admin')))->toBeFalse();
});

test('el widget sugiere a quien tiene diez reportes resueltos y al menos el 80 % aceptados', function (): void {
    $candidate = User::factory()->established()->create();
    Report::factory()->resolved(ReportStatus::Accepted)->count(8)->create(['reporter_id' => $candidate->getKey()]);
    Report::factory()->resolved(ReportStatus::Rejected)->count(2)->create(['reporter_id' => $candidate->getKey()]);

    $lowAcceptance = User::factory()->established()->create();
    Report::factory()->resolved(ReportStatus::Accepted)->count(7)->create(['reporter_id' => $lowAcceptance->getKey()]);
    Report::factory()->resolved(ReportStatus::Rejected)->count(3)->create(['reporter_id' => $lowAcceptance->getKey()]);

    $tooFew = User::factory()->established()->create();
    Report::factory()->resolved(ReportStatus::Accepted)->count(9)->create(['reporter_id' => $tooFew->getKey()]);

    Livewire::actingAs(User::factory()->admin()->withTwoFactor()->create())
        ->test(TrustCandidatesWidget::class)
        ->assertCanSeeTableRecords([$candidate])
        ->assertCanNotSeeTableRecords([$lowAcceptance, $tooFew]);
});

test('el widget de candidatos solo lo ve un admin', function (): void {
    $this->actingAs(User::factory()->moderator()->withTwoFactor()->create());

    expect(TrustCandidatesWidget::canView())->toBeFalse();
});
