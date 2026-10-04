<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Actions\DismissCopypastaReports;
use App\Filament\Admin\Resources\Copypastas\Tables\CopypastaModerationActions;
use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ModerationQueue extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $slug = 'cola-reportes';

    protected string $view = 'filament.admin.pages.moderation-queue';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isStaff();
    }

    public static function getNavigationLabel(): string
    {
        return __('moderation.queue.navigation');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Report::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function getTitle(): string
    {
        return __('moderation.queue.title');
    }

    /**
     * One row per copy-pasta with pending reports. Hidden copy-pastas come first, then the ones with most reports.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Copypasta::query()
                ->whereHas('pendingReports')
                ->with(['user:id,username', 'pendingReports'])
                ->withCount('pendingReports as pending_reports_count')
                ->withMin('pendingReports as first_reported_at', 'created_at')
                ->withMax('pendingReports as last_reported_at', 'created_at')
                ->orderByRaw('copypastas.hidden_at IS NULL')
                ->orderByDesc('pending_reports_count')
                ->orderBy('first_reported_at'))
            ->emptyStateHeading(__('moderation.queue.empty'))
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.fields.title'))
                    ->limit(60)
                    ->description(fn (Copypasta $copypasta): string => $copypasta->isHidden()
                        ? __('moderation.queue.hidden_badge')
                        : __('moderation.queue.visible_badge')),
                TextColumn::make('user.username')
                    ->label(__('admin.fields.author')),
                TextColumn::make('pending_reports_count')
                    ->label(__('moderation.queue.reports')),
                TextColumn::make('reasons')
                    ->label(__('moderation.queue.reasons'))
                    ->getStateUsing(fn (Copypasta $copypasta): string => $copypasta->pendingReports
                        ->map(fn (Report $report): string => __('moderation.reasons.'.$report->reason->value))
                        ->unique()
                        ->implode(', ')),
                TextColumn::make('first_reported_at')
                    ->label(__('moderation.queue.first_reported'))
                    ->getStateUsing(fn (Copypasta $copypasta): ?Carbon => $this->asDate($copypasta->getAttribute('first_reported_at')))
                    ->dateTime(),
                TextColumn::make('last_reported_at')
                    ->label(__('moderation.queue.last_reported'))
                    ->getStateUsing(fn (Copypasta $copypasta): ?Carbon => $this->asDate($copypasta->getAttribute('last_reported_at')))
                    ->dateTime(),
            ])
            ->recordActions([
                CopypastaModerationActions::hide(),
                CopypastaModerationActions::restore(),
                CopypastaModerationActions::toggleNsfw(),
                Action::make('dismiss')
                    ->label(__('moderation.queue.dismiss'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(__('moderation.queue.dismiss_confirm'))
                    ->authorize('dismissReports')
                    ->action(fn (Copypasta $copypasta) => app(DismissCopypastaReports::class)
                        ->handle(CopypastaModerationActions::actor(), $copypasta)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('dismiss')
                        ->label(__('moderation.queue.dismiss'))
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalDescription(__('moderation.queue.dismiss_confirm'))
                        ->action(function (Collection $copypastas): void {
                            foreach ($copypastas as $copypasta) {
                                if ($copypasta instanceof Copypasta) {
                                    app(DismissCopypastaReports::class)->handle(CopypastaModerationActions::actor(), $copypasta);
                                }
                            }
                        }),
                ]),
            ]);
    }

    private function asDate(mixed $value): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value);
    }
}
