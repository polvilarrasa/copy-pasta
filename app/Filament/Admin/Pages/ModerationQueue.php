<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Actions\DismissCopypastaReports;
use App\Actions\HideCopypasta;
use App\Actions\MarkCopypastaNsfw;
use App\Actions\RestoreCopypasta;
use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Auth\AuthenticationException;
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
                Action::make('hide')
                    ->label(__('admin.actions.hide'))
                    ->icon(Heroicon::OutlinedEyeSlash)
                    ->color('danger')
                    ->modalHeading(__('admin.actions.hide_heading'))
                    ->modalSubmitActionLabel(__('admin.actions.hide_submit'))
                    ->authorize('hide')
                    ->visible(fn (Copypasta $copypasta): bool => ! $copypasta->isHidden())
                    ->schema([
                        Textarea::make('reason')
                            ->label(__('admin.fields.reason'))
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(fn (array $data, Copypasta $copypasta) => app(HideCopypasta::class)
                        ->handle($this->actor(), $copypasta, (string) $data['reason'])),
                Action::make('restore')
                    ->label(__('admin.actions.restore'))
                    ->icon(Heroicon::OutlinedEye)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.actions.restore_heading'))
                    ->authorize('restore')
                    ->visible(fn (Copypasta $copypasta): bool => $copypasta->isHidden())
                    ->action(fn (Copypasta $copypasta) => app(RestoreCopypasta::class)
                        ->handle($this->actor(), $copypasta)),
                Action::make('toggleNsfw')
                    ->label(fn (Copypasta $copypasta): string => $copypasta->is_nsfw
                        ? __('admin.actions.unmark_nsfw')
                        : __('admin.actions.mark_nsfw'))
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->authorize('markNsfw')
                    ->action(fn (Copypasta $copypasta) => app(MarkCopypastaNsfw::class)
                        ->handle($this->actor(), $copypasta, ! $copypasta->is_nsfw)),
                Action::make('dismiss')
                    ->label(__('moderation.queue.dismiss'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(__('moderation.queue.dismiss_confirm'))
                    ->authorize('dismissReports')
                    ->action(fn (Copypasta $copypasta) => app(DismissCopypastaReports::class)
                        ->handle($this->actor(), $copypasta)),
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
                                    app(DismissCopypastaReports::class)->handle($this->actor(), $copypasta);
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

    private function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
