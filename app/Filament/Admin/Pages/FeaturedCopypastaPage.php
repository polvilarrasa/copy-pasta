<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Actions\ReplaceFeaturedCopypasta;
use App\Models\Copypasta;
use App\Models\FeaturedCopypasta;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;

/**
 * Shows the copy-pasta of the day and lets staff replace it with another one.
 */
class FeaturedCopypastaPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $slug = 'copypasta-del-dia';

    protected string $view = 'filament.admin.pages.featured-copypasta';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isStaff();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.featured.navigation');
    }

    public function getTitle(): string
    {
        return __('admin.featured.title');
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('replace')
                ->label(__('admin.featured.replace'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->modalHeading(__('admin.featured.replace_heading'))
                ->modalDescription(__('admin.featured.replace_description'))
                ->modalSubmitActionLabel(__('admin.featured.replace_submit'))
                ->schema([
                    Select::make('copypasta')
                        ->label(__('admin.featured.copypasta'))
                        ->searchable()
                        ->required()
                        ->getSearchResultsUsing(fn (string $search): array => $this->candidates($search))
                        ->getOptionLabelUsing(fn (string $value): ?string => Copypasta::query()->whereKey($value)->value('title')),
                ])
                ->action(function (array $data): void {
                    $copypasta = Copypasta::query()->findOrFail((string) $data['copypasta']);

                    try {
                        app(ReplaceFeaturedCopypasta::class)->handle($this->actor(), $copypasta);
                    } catch (ValidationException $exception) {
                        Notification::make()->title((string) collect($exception->errors())->flatten()->first())->danger()->send();

                        return;
                    }

                    Notification::make()->title(__('admin.featured.replaced'))->success()->send();
                }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'featured' => FeaturedCopypasta::query()->with(['copypasta', 'pickedBy'])->where('date', now()->toDateString())->first(),
        ];
    }

    /**
     * Visible, non-NSFW copy-pastas that were never featured, by title.
     *
     * @return array<string, string>
     */
    private function candidates(string $search): array
    {
        return Copypasta::query()
            ->visible()
            ->where('is_nsfw', false)
            ->where('title', 'ilike', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%')
            ->whereNotExists(fn ($featured) => $featured->selectRaw('1')->from('featured_copypastas')
                ->whereColumn('featured_copypastas.copypasta_id', 'copypastas.id'))
            ->orderByDesc('published_at')
            ->limit(30)
            ->pluck('title', 'id')
            ->all();
    }

    private function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
