<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\MyCopypastas\Schemas;

use App\Actions\ResolveCopypastaTags;
use App\Models\Copypasta;
use App\Models\Tag;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class CopypastaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('hidden_notice')
                    ->label(__('app.fields.hidden_notice'))
                    ->content(fn (?Copypasta $record): string => __('app.show.hidden_notice', [
                        'reason' => $record instanceof Copypasta ? (string) $record->hidden_reason : '',
                    ]))
                    ->visible(fn (?Copypasta $record): bool => $record?->isHidden() ?? false),
                TextInput::make('title')
                    ->label(__('app.fields.title'))
                    ->required()
                    ->minLength(5)
                    ->maxLength(120),
                Textarea::make('body')
                    ->label(__('app.fields.body'))
                    ->required()
                    ->minLength(10)
                    ->maxLength(10000)
                    ->rows(10)
                    ->helperText(__('app.fields.body_helper')),
                Select::make('tag_ids')
                    ->label(__('app.fields.tags'))
                    ->helperText(__('app.fields.tags_helper', ['max' => ResolveCopypastaTags::MAX_TAGS]))
                    ->options(fn (): array => Tag::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->multiple()
                    ->minItems(1)
                    ->maxItems(ResolveCopypastaTags::MAX_TAGS)
                    ->required()
                    ->rule(Rule::exists(Tag::class, 'id')->where('is_active', true)),
                Toggle::make('is_nsfw')
                    ->label(__('app.fields.is_nsfw'))
                    ->default(false),
            ]);
    }
}
