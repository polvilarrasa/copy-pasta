<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ModerationOverviewWidget extends StatsOverviewWidget
{
    public const CACHE_KEY = 'admin.overview';

    /** The counts are cached briefly: the dashboard does not need them to the second. */
    public const CACHE_MINUTES = 5;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $counts = Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_MINUTES), fn (): array => [
            'pending_reports' => Report::query()->pending()->count(),
            'published_today' => Copypasta::query()->visible()->where('published_at', '>=', now()->startOfDay())->count(),
            'published_week' => Copypasta::query()->visible()->where('published_at', '>=', now()->subDays(7))->count(),
            'new_users' => User::query()->where('created_at', '>=', now()->subDays(7))->count(),
        ]);

        return [
            Stat::make(__('admin.widgets.pending_reports'), $counts['pending_reports']),
            Stat::make(__('admin.widgets.published_today'), $counts['published_today']),
            Stat::make(__('admin.widgets.published_week'), $counts['published_week']),
            Stat::make(__('admin.widgets.new_users'), $counts['new_users']),
        ];
    }
}
