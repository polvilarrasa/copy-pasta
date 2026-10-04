<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\MyCopypastas\Pages;

use App\Actions\FindDuplicateCopypasta;
use App\Actions\PublishCopypasta;
use App\Filament\App\Resources\MyCopypastas\MyCopypastaResource;
use App\Models\Copypasta;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;

class CreateMyCopypasta extends CreateRecord
{
    protected static string $resource = MyCopypastaResource::class;

    private ?Copypasta $duplicate = null;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $this->duplicate = app(FindDuplicateCopypasta::class)->handle((string) $data['body']);

        return app(PublishCopypasta::class)->handle($this->actor(), $data);
    }

    /**
     * Duplicates are reported after publishing and never block the copy-pasta.
     */
    protected function afterCreate(): void
    {
        if ($this->duplicate === null) {
            return;
        }

        Notification::make()
            ->warning()
            ->title(__('app.duplicate.title'))
            ->body(__('app.duplicate.body'))
            ->actions([
                Action::make('view')
                    ->label(__('app.duplicate.view'))
                    ->url(route('copypastas.show', [$this->duplicate, $this->duplicate->slug])),
            ])
            ->send();
    }

    private function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
