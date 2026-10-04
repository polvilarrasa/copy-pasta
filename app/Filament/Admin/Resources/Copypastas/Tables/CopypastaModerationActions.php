<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Copypastas\Tables;

use App\Actions\HideCopypasta;
use App\Actions\MarkCopypastaNsfw;
use App\Actions\RestoreCopypasta;
use App\Models\Copypasta;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\AuthenticationException;

/**
 * Moderation row actions shared by the copy-pastas table and the moderation queue.
 */
class CopypastaModerationActions
{
    public static function hide(): Action
    {
        return Action::make('hide')
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
            ->action(fn (array $data, Copypasta $copypasta): Copypasta => app(HideCopypasta::class)
                ->handle(self::actor(), $copypasta, (string) $data['reason']));
    }

    public static function restore(): Action
    {
        return Action::make('restore')
            ->label(__('admin.actions.restore'))
            ->icon(Heroicon::OutlinedEye)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('admin.actions.restore_heading'))
            ->authorize('restore')
            ->visible(fn (Copypasta $copypasta): bool => $copypasta->isHidden())
            ->action(fn (Copypasta $copypasta): Copypasta => app(RestoreCopypasta::class)
                ->handle(self::actor(), $copypasta));
    }

    public static function toggleNsfw(): Action
    {
        return Action::make('toggleNsfw')
            ->label(fn (Copypasta $copypasta): string => $copypasta->is_nsfw
                ? __('admin.actions.unmark_nsfw')
                : __('admin.actions.mark_nsfw'))
            ->icon(Heroicon::OutlinedNoSymbol)
            ->authorize('markNsfw')
            ->action(fn (Copypasta $copypasta): Copypasta => app(MarkCopypastaNsfw::class)
                ->handle(self::actor(), $copypasta, ! $copypasta->is_nsfw));
    }

    public static function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
