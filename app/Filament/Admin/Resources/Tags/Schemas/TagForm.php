<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tags\Schemas;

use App\Enums\TagColor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(40),
                TextInput::make('slug')
                    ->label(__('admin.fields.slug'))
                    ->helperText(__('admin.fields.slug_helper'))
                    ->maxLength(40)
                    ->unique(ignoreRecord: true),
                Select::make('color')
                    ->label(__('admin.fields.color'))
                    ->options(TagColor::class)
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->default(true),
            ]);
    }
}
