<?php

declare(strict_types=1);

use App\Filament\Admin\Widgets\ModerationOverviewWidget;
use App\Models\Report;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

test('el escritorio muestra los reportes pendientes y se cachean los conteos', function (): void {
    Report::factory()->count(2)->create();

    Livewire::actingAs(User::factory()->moderator()->create())
        ->test(ModerationOverviewWidget::class)
        ->assertSee('Reportes pendientes')
        ->assertSee('2');

    Report::factory()->create();

    Livewire::actingAs(User::factory()->moderator()->create())
        ->test(ModerationOverviewWidget::class)
        ->assertSee('2')
        ->assertDontSee('3');

    expect(Cache::has(ModerationOverviewWidget::CACHE_KEY))->toBeTrue();
});
