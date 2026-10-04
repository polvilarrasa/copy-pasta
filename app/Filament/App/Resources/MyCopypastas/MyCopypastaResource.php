<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\MyCopypastas;

use App\Filament\App\Resources\MyCopypastas\Pages\CreateMyCopypasta;
use App\Filament\App\Resources\MyCopypastas\Pages\EditMyCopypasta;
use App\Filament\App\Resources\MyCopypastas\Pages\ListMyCopypastas;
use App\Filament\App\Resources\MyCopypastas\Schemas\CopypastaForm;
use App\Filament\App\Resources\MyCopypastas\Tables\MyCopypastasTable;
use App\Models\Copypasta;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyCopypastaResource extends Resource
{
    protected static ?string $model = Copypasta::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $slug = 'copypastas';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('app.models.copypasta');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.models.my_copypastas');
    }

    /**
     * Only verified, active members see this list; the Copypasta policy keeps the staff-only admin rule intact.
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasVerifiedEmail() && ! $user->isBanned();
    }

    /**
     * Members only ever reach their own copy-pastas, so other people's records return 404.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return CopypastaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MyCopypastasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyCopypastas::route('/'),
            'create' => CreateMyCopypasta::route('/create'),
            'edit' => EditMyCopypasta::route('/{record}/edit'),
        ];
    }
}
