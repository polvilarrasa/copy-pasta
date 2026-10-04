<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Filament\Admin\Resources\Reports\Pages\ListReports;
use App\Filament\Admin\Resources\Reports\ReportResource;
use App\Models\Report;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

test('el histórico muestra reportes de cualquier estado al staff', function (): void {
    $pending = Report::factory()->create();
    $resolved = Report::factory()->resolved(ReportStatus::Rejected)->create();

    Livewire::actingAs(User::factory()->moderator()->create())
        ->test(ListReports::class)
        ->assertCanSeeTableRecords([$pending, $resolved]);
});

test('un miembro normal no accede al histórico de reportes', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(ReportResource::getUrl('index'))
        ->assertForbidden();
});
