<?php

declare(strict_types=1);

use App\Filament\Admin\Widgets\ModerationOverviewWidget;
use App\Models\Report;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

test('el escritorio muestra los reportes pendientes y reutiliza los conteos cacheados', function (): void {
    Report::factory()->count(2)->create();

    Livewire::actingAs(User::factory()->moderator()->create())
        ->test(ModerationOverviewWidget::class)
        ->assertSee('Reportes pendientes');

    expect(Cache::get(ModerationOverviewWidget::CACHE_KEY)['pending_reports'])->toBe(2);

    Report::factory()->create();

    Livewire::actingAs(User::factory()->moderator()->create())
        ->test(ModerationOverviewWidget::class);

    expect(Cache::get(ModerationOverviewWidget::CACHE_KEY)['pending_reports'])->toBe(2);

    Cache::forget(ModerationOverviewWidget::CACHE_KEY);

    Livewire::actingAs(User::factory()->moderator()->create())
        ->test(ModerationOverviewWidget::class);

    expect(Cache::get(ModerationOverviewWidget::CACHE_KEY)['pending_reports'])->toBe(3);
});
