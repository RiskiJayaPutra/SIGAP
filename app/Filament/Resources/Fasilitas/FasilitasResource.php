<?php

namespace App\Filament\Resources\Fasilitas;

use App\Filament\Resources\Fasilitas\Pages\CreateFasilitas;
use App\Filament\Resources\Fasilitas\Pages\EditFasilitas;
use App\Filament\Resources\Fasilitas\Pages\ListFasilitas;
use App\Filament\Resources\Fasilitas\Schemas\FasilitasForm;
use App\Filament\Resources\Fasilitas\Tables\FasilitasTable;
use App\Models\Fasilitas;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FasilitasResource extends Resource
{
    protected static ?string $model = Fasilitas::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return FasilitasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FasilitasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['kecamatan:id,nama']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFasilitas::route('/'),
            'create' => CreateFasilitas::route('/create'),
            'edit' => EditFasilitas::route('/{record}/edit'),
        ];
    }
}
