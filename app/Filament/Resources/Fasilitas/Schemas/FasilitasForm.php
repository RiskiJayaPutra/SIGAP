<?php

namespace App\Filament\Resources\Fasilitas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Set;
use Dotswan\MapPicker\Fields\Map;
use Filament\Schemas\Schema;

class FasilitasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->required(),
                Select::make('kategori_id')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama')
                    ->required(),
                Select::make('kecamatan_id')
                    ->label('Kecamatan')
                    ->relationship('kecamatan', 'nama')
                    ->required(),
                Hidden::make('latitude'),
                Hidden::make('longitude'),
                Map::make('location')
                    ->label('Peta Lokasi (Klik atau Seret Marker)')
                    ->columnSpanFull()
                    ->defaultLocation(latitude: -5.3971, longitude: 105.2668) // Bandar Lampung default
                    ->afterStateUpdated(function ($set, ?array $state): void {
                        if (!$state) return;
                        $set('latitude',  $state['lat']);
                        $set('longitude', $state['lng']);
                        // Auto detect kecamatan based on PostGIS ST_Contains
                        // Set a higher time limit for this spatial query
                        set_time_limit(120);
                        try {
                            $kec = \App\Models\Kecamatan::whereRaw(
                                "geom IS NOT NULL AND ST_Contains(ST_GeomFromGeoJSON(geom::text), ST_SetSRID(ST_Point(?, ?), 4326))",
                                [$state['lng'], $state['lat']]
                            )->limit(1)->first();
                            if ($kec) {
                                $set('kecamatan_id', $kec->id);
                            }
                        } catch (\Exception $e) {
                            // Spatial query failed, skip auto-detect
                        }
                    })
                    ->afterStateHydrated(function ($state, $record, $set): void {
                        if($record && $record->latitude && $record->longitude) {
                            $set('location', ['lat' => $record->latitude, 'lng' => $record->longitude]);
                        }
                    })
                    ->dehydrated(false)
                    ->live(onBlur: true)
                    ->showMarker()
                    ->clickable(true)
                    ->draggable(),
                FileUpload::make('foto')
                    ->image()
                    ->directory('fasilitas-foto')
                    ->nullable(),
                Textarea::make('deskripsi')
                    ->columnSpanFull(),
            ]);
    }
}
