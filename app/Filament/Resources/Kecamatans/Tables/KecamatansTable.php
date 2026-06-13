<?php

namespace App\Filament\Resources\Kecamatans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KecamatansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn () => \App\Models\Kecamatan::query()->select(['id', 'nama', 'populasi', 'luas_wilayah', 'created_at', 'updated_at']))
            ->columns([
                TextColumn::make('nama')
                    ->searchable(),
                TextColumn::make('populasi')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('luas_wilayah')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
