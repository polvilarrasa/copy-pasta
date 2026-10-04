<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Reports;

use App\Filament\Admin\Resources\Reports\Pages\ListReports;
use App\Filament\Admin\Resources\Reports\Tables\ReportsTable;
use App\Models\Report;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $slug = 'reportes';

    public static function getModelLabel(): string
    {
        return __('moderation.log.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('moderation.log.models');
    }

    public static function getNavigationLabel(): string
    {
        return __('moderation.log.navigation');
    }

    public static function table(Table $table): Table
    {
        return ReportsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['copypasta:id,title', 'reporter:id,username', 'resolvedBy:id,username']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
        ];
    }
}
