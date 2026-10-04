<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\MyCopypastas\Pages;

use App\Actions\UpdateCopypasta;
use App\Filament\App\Resources\MyCopypastas\MyCopypastaResource;
use App\Models\Copypasta;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;

class EditMyCopypasta extends EditRecord
{
    protected static string $resource = MyCopypastaResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        throw_unless($record instanceof Copypasta, AuthenticationException::class);

        $data['tag_ids'] = $record->tags()->where('is_active', true)->pluck('tags.id')->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        throw_unless($record instanceof Copypasta, AuthenticationException::class);

        return app(UpdateCopypasta::class)->handle($this->actor(), $record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    private function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
