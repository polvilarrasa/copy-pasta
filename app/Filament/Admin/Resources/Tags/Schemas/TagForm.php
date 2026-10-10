<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tags\Schemas;

use App\Enums\TagColor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

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
                ViewField::make('color')
                    ->view('filament.forms.components.tag-color-field')
                    ->label(__('admin.fields.color'))
                    ->rule(Rule::enum(TagColor::class))
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->default(true),
            ]);
    }
}
