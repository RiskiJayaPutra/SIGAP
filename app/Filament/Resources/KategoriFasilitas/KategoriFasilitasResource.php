<?php

namespace App\Filament\Resources\KategoriFasilitas;

use App\Filament\Resources\KategoriFasilitas\Pages\CreateKategoriFasilitas;
use App\Filament\Resources\KategoriFasilitas\Pages\EditKategoriFasilitas;
use App\Filament\Resources\KategoriFasilitas\Pages\ListKategoriFasilitas;
use App\Filament\Resources\KategoriFasilitas\Schemas\KategoriFasilitasForm;
use App\Filament\Resources\KategoriFasilitas\Tables\KategoriFasilitasTable;
use App\Models\KategoriFasilitas;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KategoriFasilitasResource extends Resource
{
    protected static ?string $model = KategoriFasilitas::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return KategoriFasilitasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KategoriFasilitasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKategoriFasilitas::route('/'),
            'create' => CreateKategoriFasilitas::route('/create'),
            'edit' => EditKategoriFasilitas::route('/{record}/edit'),
        ];
    }
}
