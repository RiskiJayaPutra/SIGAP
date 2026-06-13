<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use App\Models\Kecamatan;
use App\Models\Fasilitas;

class InfoKecamatanTable extends TableWidget
{
    protected static ?string $heading = 'Informasi Wilayah per Kecamatan';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Kecamatan::query()
                    ->select(['id', 'nama', 'populasi', 'luas_wilayah'])
                    ->withCount('fasilitas')
                    ->orderByDesc('fasilitas_count')
            )
            ->columns([
                TextColumn::make('nama')
                    ->label('Kecamatan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-map-pin'),

                TextColumn::make('populasi')
                    ->label('Penduduk')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.') . ' jiwa')
                    ->color('primary'),

                TextColumn::make('luas_wilayah')
                    ->label('Luas (km²)')
                    ->sortable()
                    ->color('warning'),

                TextColumn::make('fasilitas_count')
                    ->label('Total Fasilitas')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->weight('bold'),
            ])
            ->paginated([10, 25, 50]);
    }
}
