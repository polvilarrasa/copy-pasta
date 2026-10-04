<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tags\Pages;

use App\Actions\SaveTag;
use App\Filament\Admin\Resources\Tags\TagResource;
use App\Models\Tag;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        throw_unless($record instanceof Tag, AuthenticationException::class);

        return app(SaveTag::class)->handle($this->actor(), $data, $record);
    }

    private function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
