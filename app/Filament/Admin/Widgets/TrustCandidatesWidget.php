<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Report;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Suggests members for the trusted role. It only lists them: an admin assigns the role from the member's record.
 */
class TrustCandidatesWidget extends TableWidget
{
    public const MIN_RESOLVED_REPORTS = 10;

    public const ACCEPTANCE_RATE_PERCENT = 80;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    public function getTableHeading(): string
    {
        return __('admin.widgets.trust_candidates');
    }

    public function table(Table $table): Table
    {
        $resolved = [ReportStatus::Accepted->value, ReportStatus::Rejected->value];

        return $table
            ->query(fn (): Builder => User::query()
                ->where('role', Role::User)
                ->whereNull('banned_at')
                ->addSelect([
                    'resolved_reports' => Report::query()->selectRaw('count(*)')
                        ->whereColumn('reporter_id', 'users.id')
                        ->whereIn('status', $resolved),
                    'accepted_reports' => Report::query()->selectRaw('count(*)')
                        ->whereColumn('reporter_id', 'users.id')
                        ->where('status', ReportStatus::Accepted),
                ])
                ->whereRaw(
                    '(SELECT COUNT(*) FROM reports WHERE reports.reporter_id = users.id AND reports.status IN (?, ?)) >= ?',
                    [...$resolved, self::MIN_RESOLVED_REPORTS],
                )
                ->whereRaw(
                    '(SELECT COUNT(*) FROM reports WHERE reports.reporter_id = users.id AND reports.status = ?) * 100 >= '
                    .'(SELECT COUNT(*) FROM reports WHERE reports.reporter_id = users.id AND reports.status IN (?, ?)) * ?',
                    [ReportStatus::Accepted->value, ...$resolved, self::ACCEPTANCE_RATE_PERCENT],
                ))
            ->columns([
                TextColumn::make('username')->label(__('admin.fields.username')),
                TextColumn::make('resolved_reports')->label(__('admin.widgets.resolved_reports')),
                TextColumn::make('accepted_reports')->label(__('admin.widgets.accepted_reports')),
            ])
            ->emptyStateHeading(__('admin.widgets.trust_candidates_hint'));
    }
}
