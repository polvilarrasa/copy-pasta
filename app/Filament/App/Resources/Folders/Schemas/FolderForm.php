<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Folders\Schemas;

use App\Models\Folder;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class FolderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::fields());
    }

    /**
     * Shared by the resource form and the rename and create modals. Uniqueness and the limit are checked here so
     * the error appears next to the field; the Actions repeat both checks and stay the authority.
     *
     * @return array<int, TextInput>
     */
    public static function fields(?Folder $record = null): array
    {
        return [
            TextInput::make('name')
                ->label(__('app.fields.folder_name'))
                ->required()
                ->maxLength(50)
                ->rules([
                    Rule::unique(Folder::class, 'name')
                        ->where('user_id', auth()->id())
                        ->ignore($record),
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        $reachedLimit = Folder::query()->where('user_id', auth()->id())->count() >= Folder::MAX_PER_USER;

                        if ($record === null && $reachedLimit) {
                            $fail(__('app.folders.errors.limit', ['max' => Folder::MAX_PER_USER]));
                        }
                    },
                ]),
        ];
    }
}
